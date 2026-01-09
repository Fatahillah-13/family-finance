<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionImport;
use App\Models\TransactionImportRow;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use illuminate\Support\Str;

class ImportTransactionController extends Controller
{
    public function create(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $accounts = Account::query()
            ->where('household_id', $hid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('imports.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $validated = $request->validate([
            'account_id' => ['required', 'integer'],
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            // mapping columns (0-based index)
            'col_date' => ['required', 'integer', 'min:0'],
            'col_description' => ['required', 'integer', 'min:0'],
            'col_amount' => ['required', 'integer', 'min:0'],
            'col_type' => ['nullable', 'integer', 'min:0'],
            'has_header' => ['nullable'],
            // date format for parsing
            'date_format' => ['required', 'string'], // e.g. Y-m-d, d/m/Y
        ]);

        $account = Account::query()
            ->where('household_id', $hid)
            ->where('id', $validated['account_id'])
            ->firstOrFail();

        $file = $request->file('csv');
        $path = $file->getRealPath();
        $hasHeader = (bool) $request->boolean('has_header');

        $import = TransactionImport::create([
            'household_id' => $hid,
            'account_id' => $account->id,
            'created_by' => $user->id,
            'original_filename' => $file->getClientOriginalName(),
            'status' => 'draft',
        ]);

        $rows = $this->readCsv($path);

        if ($hasHeader && count($rows) > 0) {
            array_shift($rows);
        }

        $colDate = (int) $validated['col_date'];
        $colDesc = (int) $validated['col_description'];
        $colAmount = (int) $validated['col_amount'];
        $colType = $validated['col_type'] !== null ? (int) $validated['col_type'] : null;
        $dateFormat = $validated['date_format'];

        $toInsert = [];
        foreach ($rows as $i => $cols) {
            if (!is_array($cols) || count($cols) === 0) continue;

            $dateRaw = $cols[$colDate] ?? null;
            $descRaw = $cols[$colDesc] ?? null;
            $amountRaw = $cols[$colAmount] ?? null;
            $typeRaw = $colType !== null ? ($cols[$colType] ?? null) : null;

            if ($dateRaw === null || $amountRaw === null) {
                continue;
            }

            $occurredDate = $this->parseDate($dateRaw, $dateFormat);
            if (!$occurredDate) {
                continue;
            }

            $desc = trim((string) $descRaw);
            $parsed = $this->parseAmountAndType((string) $amountRaw, (string) $typeRaw);
            if ($parsed === null) {
                continue;
            }

            [$amount, $type] = $parsed; // amount int positive, type income|expense

            // hash includes account + date + amount + description (normalized)
            $normDesc = mb_strtolower(trim(preg_replace('/\s+/', ' ', $desc)));
            $hash = hash('sha256', $account->id . '|' . $occurredDate->format('Y-m-d') . '|' . $amount . '|' . $normDesc);

            $toInsert[] = [
                'transaction_import_id' => $import->id,
                'occurred_date' => $occurredDate->format('Y-m-d'),
                'description' => $desc !== '' ? $desc : null,
                'amount' => $amount,
                'type' => $type,
                'hash' => $hash,
                'raw' => json_encode([
                    'row_index' => $i,
                    'date' => $dateRaw,
                    'description' => $descRaw,
                    'amount' => $amountRaw,
                    'type' => $typeRaw,
                    'cols' => $cols,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // bulk insert
        if (!empty($toInsert)) {
            // avoid duplicates inside same import by hash
            $unique = collect($toInsert)->unique('hash')->values()->all();
            DB::table('transaction_import_rows')->insert($unique);
        }

        return redirect()->route('imports.preview', $import);
    }

    public function preview(Request $request, TransactionImport $import)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $import->household_id === $hid, 403);

        $import->load('account');

        $rows = TransactionImportRow::query()
            ->where('transaction_import_id', $import->id)
            ->orderBy('occurred_date')
            ->orderBy('id')
            ->get();

        // check duplicates against existing transactions by recomputing hash logic
        // We'll compute "existing hashes" by selecting candidate transactions for the account and date range.
        $minDate = $rows->min('occurred_date');
        $maxDate = $rows->max('occurred_date');

        $existing = collect();
        if ($minDate && $maxDate) {
            $candidates = Transaction::query()
                ->whereNull('deleted_at')
                ->where('household_id', $hid)
                ->where('account_id', $import->account_id)
                ->whereBetween('occurred_at', [
                    Carbon::parse($minDate)->startOfDay(),
                    Carbon::parse($maxDate)->endOfDay()
                ])
                ->get(['id', 'occurred_at', 'amount', 'description', 'type']);

            $existing = $candidates->map(function ($t) use ($import) {
                $normDesc = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $t->description)));
                $date = Carbon::parse($t->occurred_at)->format('Y-m-d');
                return hash('sha256', $import->account_id . '|' . $date . '|' . ((int)$t->amount) . '|' . $normDesc);
            })->flip(); // set-like
        }

        $preview = $rows->map(function (TransactionImportRow $r) use ($existing) {
            return [
                'id' => $r->id,
                'occurred_date' => $r->occurred_date->format('Y-m-d'),
                'type' => $r->type,
                'amount' => $r->amount,
                'description' => $r->description,
                'is_duplicate' => $existing->has($r->hash),
            ];
        });

        $counts = [
            'total' => $preview->count(),
            'duplicates' => $preview->where('is_duplicate', true)->count(),
            'new' => $preview->where('is_duplicate', false)->count(),
        ];

        return view('imports.preview', [
            'import' => $import,
            'preview' => $preview,
            'counts' => $counts,
        ]);
    }

    public function commit(Request $request, TransactionImport $import)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $import->household_id === $hid, 403);

        abort_unless($import->status === 'draft', 422);

        $rows = TransactionImportRow::query()
            ->where('transaction_import_id', $import->id)
            ->orderBy('occurred_date')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return back()->withErrors(['import' => 'Tidak ada baris valid untuk di-import.']);
        }

        // Default categories "Lainnya" for income and expense
        $incomeOther = Category::query()
            ->where('household_id', $hid)
            ->where('type', 'income')
            ->where('name', 'Lainnya')
            ->first();

        $expenseOther = Category::query()
            ->where('household_id', $hid)
            ->where('type', 'expense')
            ->where('name', 'Lainnya')
            ->first();

        // If not exist, fallback to any category of correct type
        $fallbackIncomeCatId = $incomeOther?->id
            ?? Category::query()->where('household_id', $hid)->where('type', 'income')->value('id');

        $fallbackExpenseCatId = $expenseOther?->id
            ?? Category::query()->where('household_id', $hid)->where('type', 'expense')->value('id');

        abort_unless($fallbackIncomeCatId && $fallbackExpenseCatId, 422);

        // existing hash set (same as preview)
        $minDate = $rows->min('occurred_date');
        $maxDate = $rows->max('occurred_date');

        $existing = collect();
        if ($minDate && $maxDate) {
            $candidates = Transaction::query()
                ->whereNull('deleted_at')
                ->where('household_id', $hid)
                ->where('account_id', $import->account_id)
                ->whereBetween('occurred_at', [
                    Carbon::parse($minDate)->startOfDay(),
                    Carbon::parse($maxDate)->endOfDay()
                ])
                ->get(['id', 'occurred_at', 'amount', 'description', 'type']);

            $existing = $candidates->map(function ($t) use ($import) {
                $normDesc = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $t->description)));
                $date = Carbon::parse($t->occurred_at)->format('Y-m-d');
                return hash('sha256', $import->account_id . '|' . $date . '|' . ((int)$t->amount) . '|' . $normDesc);
            })->flip();
        }

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $rows,
            $existing,
            $hid,
            $import,
            $user,
            $fallbackIncomeCatId,
            $fallbackExpenseCatId,
            &$created,
            &$skipped
        ) {
            foreach ($rows as $r) {
                if ($existing->has($r->hash)) {
                    $skipped++;
                    continue;
                }

                $type = $r->type === 'income' ? 'income' : 'expense';
                $categoryId = $type === 'income' ? $fallbackIncomeCatId : $fallbackExpenseCatId;

                Transaction::create([
                    'household_id' => $hid,
                    'type' => $type,
                    // occurred_at at 00:00
                    'occurred_at' => Carbon::parse($r->occurred_date)->startOfDay(),
                    'description' => $r->description,
                    'amount' => (int) $r->amount,
                    'account_id' => $import->account_id,
                    'category_id' => $categoryId,
                    'from_account_id' => null,
                    'to_account_id' => null,
                    'created_by' => $user->id,
                ]);

                $created++;
            }

            $import->status = 'committed';
            $import->save();
        });

        return redirect()->route('imports.preview', $import)
            ->with('status', "Import selesai. Created: {$created}, skipped (duplicate): {$skipped}");
    }

    private function readCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');
        if (!$handle) return $rows;

        // Try detect delimiter ; or ,
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            return [];
        }
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        // rewind
        rewind($handle);

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            // skip completely empty lines
            if (count($data) === 1 && trim((string)$data[0]) === '') continue;
            $rows[] = $data;
        }
        fclose($handle);

        return $rows;
    }

    private function parseDate(string $raw, string $format): ?Carbon
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        try {
            return Carbon::createFromFormat($format, $raw);
        } catch (\Throwable $e) {
            // fallback: try parse
            try {
                return Carbon::parse($raw);
            } catch (\Throwable $e2) {
                return null;
            }
        }
    }

    /**
     * Returns [amount(int positive), type(income|expense)] or null.
     * Supports: "Rp 15.000", "-Rp 15.000", "(Rp 15.000)".
     * typeRaw can be "debit/credit", "D/C", "income/expense".
     */

    private function parseAmountAndType(string $amountRaw, string $typeRaw): ?array
    {
        $s = trim($amountRaw);
        if ($s === '') return null;

        $negative = false;

        // parentheses meaning negative
        if (Str::startsWith($s, '(') && Str::endsWith($s, ')')) {
            $negative = true;
            $s = trim($s, '() ');
        }

        // explicit minus
        if (Str::startsWith($s, '-')) {
            $negative = true;
            $s = ltrim($s, '- ');
        }

        // remove currency + spaces
        $s = str_ireplace(['rp', 'idr'], '', $s);
        $s = trim($s);

        // normalize thousand separators:
        // "15.000" => 15000 ; "15,000" => 15000
        $s = str_replace(['.', ',', ' '], '', $s);

        if (!ctype_digit($s)) return null;

        $amount = (int) $s;
        if ($amount <= 0) return null;

        $typeRawNorm = mb_strtolower(trim($typeRaw));

        $type = null;
        if (in_array($typeRawNorm, ['income', 'in', 'credit', 'cr', 'c'], true)) $type = 'income';
        if (in_array($typeRawNorm, ['expense', 'out', 'debit', 'db', 'd'], true)) $type = 'expense';

        // infer from negative flag
        if ($type === null) {
            $type = $negative ? 'expense' : 'income';
        }

        return [$amount, $type];
    }
}
