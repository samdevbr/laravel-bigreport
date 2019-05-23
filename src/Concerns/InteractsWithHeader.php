<?php
namespace Samdevbr\Bigreport\Concerns;

interface InteractsWithHeader
{
    public function handleHeader(array $headings): array;
}
