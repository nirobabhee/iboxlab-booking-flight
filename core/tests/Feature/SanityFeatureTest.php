<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

final class SanityFeatureTest extends TestCase
{
    public function test_feature_suite_runs(): void
    {
        $this->assertTrue(true);
    }
}
