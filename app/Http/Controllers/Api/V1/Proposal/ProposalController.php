<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Proposal;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Proposal\CreateProposalRequest;
use App\Http\Requests\Api\V1\Proposal\ProposalActionRequest;
use App\Http\Requests\Api\V1\Proposal\ProposalListRequest;
use App\Http\Requests\Api\V1\Proposal\StoreProposalNoteRequest;
use App\Http\Requests\Api\V1\Proposal\ToggleUserListRequest;
use App\Http\Resources\Api\V1\Proposal\ProposalEventResource;
use App\Http\Resources\Api\V1\Proposal\ProposalNoteResource;
use App\Http\Resources\Api\V1\Proposal\ProposalResource;
use App\Http\Resources\Api\V1\Search\SearchProfileResource;
use App\Services\Api\V1\Proposal\ProposalService;
use Illuminate\Http\JsonResponse;

class ProposalController extends ApiController
{
    public function __construct(private readonly ProposalService $proposals)
    {
    }

    public function index(ProposalListRequest $request): JsonResponse
    {
        return ProposalResource::collection(
            $this->proposals->list($request->user(), $request->validated())
        )->additional(['success' => true])->response();
    }

    public function store(CreateProposalRequest $request): JsonResponse
    {
        $proposal = $this->proposals->create(
            $request->user(),
            (int) $request->validated('user_id'),
            $request->validated('note')
        );

        return $this->success(new ProposalResource($proposal), 'Proposal sent successfully.', 201);
    }

    public function accept(ProposalActionRequest $request, int $proposal): JsonResponse
    {
        return $this->success(
            new ProposalResource($this->proposals->accept($request->user(), $proposal, $request->validated('note'))),
            'Proposal accepted successfully.'
        );
    }

    public function reject(ProposalActionRequest $request, int $proposal): JsonResponse
    {
        return $this->success(
            new ProposalResource($this->proposals->reject($request->user(), $proposal, $request->validated('note'))),
            'Proposal rejected successfully.'
        );
    }

    public function withdraw(ProposalActionRequest $request, int $proposal): JsonResponse
    {
        return $this->success(
            new ProposalResource($this->proposals->withdraw($request->user(), $proposal, $request->validated('note'))),
            'Proposal withdrawn successfully.'
        );
    }

    public function cancel(ProposalActionRequest $request, int $proposal): JsonResponse
    {
        return $this->success(
            new ProposalResource($this->proposals->cancel($request->user(), $proposal, $request->validated('note'))),
            'Proposal cancelled successfully.'
        );
    }

    public function addNote(StoreProposalNoteRequest $request, int $proposal): JsonResponse
    {
        $note = $this->proposals->addNote($request->user(), $proposal, $request->validated('note'));

        return $this->success(new ProposalNoteResource($note), 'Proposal note added successfully.', 201);
    }

    public function timeline(ProposalActionRequest $request, int $proposal): JsonResponse
    {
        return $this->success(
            ProposalEventResource::collection($this->proposals->timeline($request->user(), $proposal)),
            'Proposal timeline fetched successfully.'
        );
    }

    public function favourite(ToggleUserListRequest $request): JsonResponse
    {
        $this->proposals->favourite($request->user(), (int) $request->validated('user_id'));

        return $this->success(message: 'Profile added to favourites.');
    }

    public function favourites(ProposalActionRequest $request): JsonResponse
    {
        $items = $this->proposals->favourites($request->user(), min((int) $request->integer('per_page', 20), 50));

        return SearchProfileResource::collection($items->through(fn ($shortlist) => $shortlist->user))
            ->additional(['success' => true])
            ->response();
    }

    public function checkFavourite(ProposalActionRequest $request, int $user): JsonResponse
    {
        return $this->success([
            'user_id' => $user,
            'is_favourite' => $this->proposals->isFavourite($request->user(), $user),
        ]);
    }

    public function removeFavourite(ProposalActionRequest $request, int $user): JsonResponse
    {
        $this->proposals->removeFavourite($request->user(), $user);

        return $this->success(message: 'Profile removed from favourites.');
    }

    public function ignore(ToggleUserListRequest $request): JsonResponse
    {
        $this->proposals->ignore($request->user(), (int) $request->validated('user_id'));

        return $this->success(message: 'Profile ignored successfully.');
    }

    public function removeIgnore(ProposalActionRequest $request, int $user): JsonResponse
    {
        $this->proposals->removeIgnore($request->user(), $user);

        return $this->success(message: 'Profile removed from ignored list.');
    }
}
