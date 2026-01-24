<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Models\User;
use Carbon\Carbon;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Http\Resources\TransactionCardResource;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        return view('transactions.index');
    }

    public function data(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'month' => ['nullable', 'date_format:Y-m'],
            'q' => ['nullable', 'string', 'max:200'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $type = $validated['type'];
        $month = $validated['month'] ?? now()->format('Y-m');
        $q = $validated['q'] ?? null;

        [$year, $mon] = explode('-', $month);
        $from = Carbon::create((int) $year, (int) $mon, 1)->startOfDay();
        $to = (clone $from)->endOfMonth()->endOfDay();

        $query = Transaction::query()
            ->where('transactions.household_id', $hid)
            ->where('transactions.type', $type)
            ->whereBetween('transactions.occurred_at', [$from, $to])
            ->select([
                'transactions.id',
                'transactions.type',
                'transactions.occurred_at',
                'transactions.amount',
                'transactions.description',
                'transactions.account_id',
                'transactions.category_id',
                'transactions.from_account_id',
                'transactions.to_account_id',
            ])
            // Eager load untuk tampilan card (ambil kolom minimal)
            ->with([
                'account:id,name',
                'category:id,name',
                'fromAccount:id,name',
                'toAccount:id,name',
            ]);

        // Search: jangan sampai null/blank berubah jadi LIKE "%%"
        if (!blank($q)) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $query->where('transactions.description', 'like', $like);
        }

        $perPage = 20;
        $paginator = $query
            ->orderByDesc('transactions.occurred_at')
            ->paginate($perPage);

        return response()->json([
            'data' => TransactionCardResource::collection($paginator->getCollection())->resolve(),
            'meta' => [
                'month' => $month,
                'current_page' => $paginator->currentPage(),
                'has_more' => $paginator->hasMorePages(),
                'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null,
            ],
        ]);
    }

    public function create(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $type = $request->query('type', 'expense');
        abort_unless(in_array($type, ['income', 'expense', 'transfer'], true), 404);

        $accounts = Account::query()
            ->where('household_id', $hid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->where('household_id', $hid)
            ->where('type', $type === 'income' ? 'income' : 'expense')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $tags = Tag::query()
            ->where('household_id', $hid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('transactions.create', compact('type', 'accounts', 'categories', 'tags'));
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'occurred_at' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:2000'],

            // income/expense
            'account_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],

            // transfer
            'from_account_id' => ['nullable', 'integer'],
            'to_account_id' => ['nullable', 'integer'],

            'tags' => ['array'],
            'tags.*' => ['integer'],

            'attachments' => ['array'],
            'attachments.*' => ['file', 'max:5120'], // 5MB per file
        ]);

        // Validate ownership (household scope)
        $type = $validated['type'];

        $tx = new Transaction();
        $tx->household_id = $hid;
        $tx->type = $type;
        $tx->occurred_at = $validated['occurred_at'];
        $tx->amount = (int) $validated['amount'];
        $tx->description = $validated['description'] ?? null;
        $tx->created_by = $user->id;

        if (in_array($type, ['income', 'expense'], true)) {
            $accountId = (int) ($validated['account_id'] ?? 0);
            $categoryId = (int) ($validated['category_id'] ?? 0);

            $account = Account::query()->where('household_id', $hid)->where('id', $accountId)->firstOrFail();
            $category = Category::query()
                ->where('household_id', $hid)
                ->where('id', $categoryId)
                ->where('type', $type === 'income' ? 'income' : 'expense')
                ->firstOrFail();

            $tx->account_id = $account->id;
            $tx->category_id = $category->id;
            $tx->from_account_id = null;
            $tx->to_account_id = null;
        } else {
            $fromId = (int) ($validated['from_account_id'] ?? 0);
            $toId = (int) ($validated['to_account_id'] ?? 0);

            abort_if($fromId === $toId, 422, 'From account dan To account tidak boleh sama.');

            $from = Account::query()->where('household_id', $hid)->where('id', $fromId)->firstOrFail();
            $to = Account::query()->where('household_id', $hid)->where('id', $toId)->firstOrFail();

            $tx->account_id = null;
            $tx->category_id = null;
            $tx->from_account_id = $from->id;
            $tx->to_account_id = $to->id;
        }

        $tx->save();

        // Tags (only for income/expense; transfer: we still allow tags? biasanya tidak perlu)
        $tagIds = collect($validated['tags'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($tagIds->isNotEmpty()) {
            $validTagIds = Tag::query()
                ->where('household_id', $hid)
                ->whereIn('id', $tagIds)
                ->pluck('id')
                ->all();

            $tx->tags()->sync($validTagIds);
        }

        // Attachments (private disk)
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store("transactions/{$hid}/{$tx->id}", 'private');

                TransactionAttachment::create([
                    'transaction_id' => $tx->id,
                    'disk' => 'private',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $user->id,
                ]);

                // setelah $transaction = Transaction::create(...)
                $attachmentId = $request->input('attachment_id');

                if ($attachmentId) {
                    TransactionAttachment::query()
                        ->where('id', $attachmentId)
                        ->whereNull('transaction_id')
                        ->where('uploaded_by', $user->id)
                        ->update(['transaction_id' => $tx->id]);
                }
            }
        }

        Audit::log($hid, $user, 'transactions.create', 'Transaction', $tx->id, [
            'type' => $tx->type,
            'amount' => $tx->amount,
        ]);

        return redirect()->route('transactions.index');
    }

    public function edit(Request $request, Transaction $transaction)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $transaction->household_id === $hid, 403);

        $transaction->load(['tags', 'attachments']);

        $type = $transaction->type;

        $accounts = Account::query()
            ->where('household_id', $hid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->where('household_id', $hid)
            ->where('type', $type === 'income' ? 'income' : 'expense')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $tags = Tag::query()
            ->where('household_id', $hid)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('transactions.edit', compact('transaction', 'type', 'accounts', 'categories', 'tags'));
    }

    public function update(Request $request, Transaction $transaction)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $transaction->household_id === $hid, 403);

        $validated = $request->validate([
            'occurred_at' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:2000'],

            'account_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'from_account_id' => ['nullable', 'integer'],
            'to_account_id' => ['nullable', 'integer'],

            'tags' => ['array'],
            'tags.*' => ['integer'],

            'attachments' => ['array'],
            'attachments.*' => ['file', 'max:5120'],
        ]);

        $transaction->occurred_at = $validated['occurred_at'];
        $transaction->amount = (int) $validated['amount'];
        $transaction->description = $validated['description'] ?? null;

        $type = $transaction->type;

        if (in_array($type, ['income', 'expense'], true)) {
            $accountId = (int) ($validated['account_id'] ?? 0);
            $categoryId = (int) ($validated['category_id'] ?? 0);

            $account = Account::query()->where('household_id', $hid)->where('id', $accountId)->firstOrFail();
            $category = Category::query()
                ->where('household_id', $hid)
                ->where('id', $categoryId)
                ->where('type', $type === 'income' ? 'income' : 'expense')
                ->firstOrFail();

            $transaction->account_id = $account->id;
            $transaction->category_id = $category->id;
            $transaction->from_account_id = null;
            $transaction->to_account_id = null;
        } else {
            $fromId = (int) ($validated['from_account_id'] ?? 0);
            $toId = (int) ($validated['to_account_id'] ?? 0);

            abort_if($fromId === $toId, 422, 'From account dan To account tidak boleh sama.');

            $from = Account::query()->where('household_id', $hid)->where('id', $fromId)->firstOrFail();
            $to = Account::query()->where('household_id', $hid)->where('id', $toId)->firstOrFail();

            $transaction->account_id = null;
            $transaction->category_id = null;
            $transaction->from_account_id = $from->id;
            $transaction->to_account_id = $to->id;
        }

        $transaction->save();

        $tagIds = collect($validated['tags'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $validTagIds = Tag::query()
            ->where('household_id', $hid)
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->all();

        $transaction->tags()->sync($validTagIds);

        // New attachments append
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store("transactions/{$hid}/{$transaction->id}", 'private');

                TransactionAttachment::create([
                    'transaction_id' => $transaction->id,
                    'disk' => 'private',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $user->id,
                ]);
            }
        }

        Audit::log($hid, $user, 'transactions.update', 'Transaction', $transaction->id, [
            'changes' => $transaction->getChanges(),
        ]);

        return redirect()->route('transactions.index');
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid && $transaction->household_id === $hid, 403);

        $transaction->delete();

        Audit::log($hid, $user, 'transactions.delete', 'Transaction', $transaction->id);

        return redirect()->route('transactions.index');
    }

    public function downloadAttachment(Request $request, TransactionAttachment $attachment)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;

        $attachment->load('transaction');

        abort_unless($hid && $attachment->transaction->household_id === $hid, 403);

        return response()->download(
            Storage::disk($attachment->disk)->path($attachment->path),
            $attachment->original_name
        );
    }

    public function deleteAttachment(Request $request, TransactionAttachment $attachment)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;

        $attachment->load('transaction');

        abort_unless($hid && $attachment->transaction->household_id === $hid, 403);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return back();
    }
}
