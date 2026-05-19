<?php

namespace App\Http\Controllers\WhatsTrigger;

use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsTrigger\ImportContactsRequest;
use App\Http\Requests\WhatsTrigger\StoreContactRequest;
use App\Http\Requests\WhatsTrigger\UpdateContactRequest;
use App\Models\Contact;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $contacts = Contact::forUser($request->user()->id)
            ->when($request->tag, fn ($q) => $q->withTag($request->tag))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            }))
            ->when($request->opted_in !== null, fn ($q) => $q->where('opted_in', $request->boolean('opted_in')))
            ->orderBy('name')
            ->paginate(50);

        return response()->json($contacts);
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = Contact::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json($contact, 201);
    }

    public function show(Request $request, Contact $contact): JsonResponse
    {
        abort_if($contact->user_id !== $request->user()->id, 403);

        return response()->json($contact);
    }

    public function update(UpdateContactRequest $request, Contact $contact): JsonResponse
    {
        abort_if($contact->user_id !== $request->user()->id, 403);

        $contact->update($request->validated());

        return response()->json($contact->fresh());
    }

    public function destroy(Request $request, Contact $contact): JsonResponse
    {
        abort_if($contact->user_id !== $request->user()->id, 403);

        $contact->delete();

        return response()->json(null, 204);
    }

    public function import(ImportContactsRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $rows = $request->validated('contacts');
        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($userId, $rows, &$created, &$skipped) {
            foreach ($rows as $row) {
                try {
                    Contact::create([
                        'user_id' => $userId,
                        'name' => $row['name'],
                        'phone' => $row['phone'],
                        'tags' => $row['tags'] ?? [],
                        'opted_in' => $row['opted_in'] ?? true,
                    ]);
                    $created++;
                } catch (UniqueConstraintViolationException) {
                    $skipped++;
                }
            }
        });

        return response()->json([
            'created' => $created,
            'skipped' => $skipped,
        ], 201);
    }
}
