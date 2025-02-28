<?php

namespace Expose\Client\Contracts;

use Expose\Client\Logger\LoggedRequest;
use Illuminate\Support\Collection;

interface LogStorageContract
{

    public function get(): ?Collection;

    public function requests(): self;

    public function withResponses(): self;

    public function delete(?string $id = null): void;

    public function find(string $id): ?LoggedRequest;

    public function getRequestList(): ?Collection;
}
