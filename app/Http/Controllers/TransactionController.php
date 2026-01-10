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

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $hid = $user->active_household_id;
        abort_unless($hid, 403);

        // Filters
        $type = $request->string('type')->toString();
        $dateFrom = $request->string('date_from')->toString(); // YYYY-MM-DD
        $dateTo = $request->string('date_to')->toString();     // YYYY-MM-DD
        $accountId = $request->integer('account_id');
        $categoryId = $request->integer('category_id');
        $tagId = $request->integer('tag_id');
        $q = trim($request->string('q')->toString());

        $query = Transaction::query()
            ->where('transactions.household_id', $hid)
            ->whereNull('transactions.deleted_at');

        // Type
        if (in_array($type, ['income', 'expense', 'transfer'], true)) {
            $query->where('transactions.type', $type);
        }

        // Date range (use occurred_at date)
        if ($dateFrom !== '') {
            $from = Carbon::parse($dateFrom)->startOfDay();
            $query->where('transactions.occurred_at', '>=', $from);
        }
        if ($dateTo !== '') {
            $to = Carbon::parse($dateTo)->endOfDay();
            $query->where('transactions.occurred_at', '<=', $to);
        }

        // Account filter:
        // - income/expense: match account_id
        // - transfer: match from OR to
        if ($accountId) {
            $query->where(function ($qq) use ($accountId) {
                $qq->where('transactions.account_id', $accountId)
                    ->orWhere('transactions.from_account_id', $accountId)
                    ->orWhere('transactions.to_account_id', $accountId);
            });
        }

        // Category filter (income/expense only logically, but safe)
        if ($categoryId) {
            $query->where('transactions.category_id', $categoryId);
        }

        // Tag filter (exists on pivot)
        if ($tagId) {
            $query->whereExists(function ($sub) use ($tagId) {
                $sub->selectRaw(1)
                    ->from('transaction_tag')
                    ->whereColumn('transaction_tag.transaction_id', 'transactions.id')
                    ->where('transaction_tag.tag_id', $tagId);
            });
        }

        // Search (description + account + category + transfer accounts)
        if ($q !== '') {
            // join for searching names
            $query
                ->leftJoin('accounts as a', 'a.id', '=', 'transactions.account_id')
                ->leftJoin('categories as c', 'c.id', '=', 'transactions.category_id')
                ->leftJoin('accounts as fa', 'fa.id', '=', 'transactions.from_account_id')
                ->leftJoin('accounts as ta', 'ta.id', '=', 'transactions.to_account_id')
                ->where(function ($qq) use ($q) {
                    $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
                    $qq->where('transactions.description', 'like', $like)
                        ->orWhere('a.name', 'like', $like)
                        ->orWhere('c.name', 'like', $like)
                        ->orWhere('fa.name', 'like', $like)
                        ->orWhere('ta.name', 'like', $like);
                })
                ->select('transactions.*'); // important to avoid selecting joined columns into model
        }

        // Eager-load relations for display
        $query->with(['account', 'category', 'fromAccount', 'toAccount', 'tags']);

        $transactions = $query
            ->orderByDesc('transactions.occurred_at')
            ->paginate(20)
            ->withQueryString();

        // Dropdown data
        $accounts = Account::query()
            ->where('household_id', $hid)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->where('household_id', $hid)
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        $tags = Tag::query()
            ->where('household_id', $hid)
            ->orderBy('name')
            ->get();

        return view('transactions.index', [
            'transactions' => $transactions,
            'accounts' => $accounts,
            'categories' => $categories,
            'tags' => $tags,

            // current filters (for form values)
            'f' => [
                'type' => $type,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'tag_id' => $tagId,
                'q' => $q,
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

        Audit::log($hid, $user, 'transactions.update', 'Transaction', $$transaction->id, [
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
