<?php

namespace App\Services\Ai;

interface AiProvider
{
    public function complete(AiRequest $req): AiResult;

    public function classify(AiRequest $req): AiResult;
}
