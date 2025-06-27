<?php

namespace BMCLibrary\Utils;

use BMCLibrary\Contracts\ApiResponseInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse implements ApiResponseInterface
{
    protected $success = false;
    protected $message = '';
    protected $status = 402;
    protected $data = null;
    protected $validation = null;
    protected $pagination = null;

    public function call(Result $result, ?int $status = HttpStatus::OK): JsonResponse
    {
        $this->setResponseProperties($result, $status);
        $this->handleData($result);
        $this->validateDataConsistency();

        return $this->build();
    }

    private function setResponseProperties(Result $result, int $status): void
    {
        $this->success = $result->success;
        $this->message = $result->error ?? $result->message;
        $this->status = $result->status ?: $status;
    }

    private function handleData(Result $result): void
    {
        if ($this->isPaginatedData($result->data)) {
            $this->handlePaginatedData($result->data);
        } else {
            $this->data = $result->data;
        }
    }

    private function isPaginatedData($data): bool
    {
        return $data instanceof LengthAwarePaginator;
    }

    private function handlePaginatedData(LengthAwarePaginator $paginator): void
    {
        $this->data = $paginator->items();
        $this->addPagination($paginator);
    }

    private function validateDataConsistency(): void
    {
        if ($this->isSuccessStatusWithNoData()) {
            $this->status = HttpStatus::NOT_FOUND;
        }
    }

    private function isSuccessStatusWithNoData(): bool
    {
        return $this->status === HttpStatus::OK && $this->data === null;
    }

    protected function addPagination(LengthAwarePaginator $paginator): void
    {
        $perPage = $paginator->perPage();
        $nextPageUrl = $paginator->nextPageUrl();
        $prevPageUrl = $paginator->previousPageUrl();
        $this->pagination = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'next_page_url' => $nextPageUrl ? $nextPageUrl . '&per_page=' . $perPage : null,
            'prev_page_url' => $prevPageUrl ? $prevPageUrl . '&per_page=' . $perPage : null,
        ];
    }

    public function success(bool $success): self
    {
        $this->success = $success;
        return $this;
    }

    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function status(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function data($data): self
    {
        $this->data = $data;
        return $this;
    }

    public function validation($data): self
    {
        $this->validation = $data;
        return $this;
    }

    public function build(): JsonResponse
    {
        $response = [
            //'success' => $this->success
        ];

        if ($this->validation != null) {
            $response['validations'] = $this->validation;
        }

        if ($this->pagination != null) {
            $response['pagination'] = $this->pagination;
        }

        if ($this->message != null) {
            $response['message'] = $this->message;
        }

        if ($this->data != null && !empty($this->data)) {
            $response['data'] = $this->data;
        }

        return response()->json($response, $this->status);
    }
}
