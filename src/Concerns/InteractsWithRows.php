<?php
namespace Samdevbr\Bigreport\Concerns;

interface InteractsWithRows
{
    public function onEachRow(array $row): array;
}
