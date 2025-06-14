<?php

namespace Branzia\Blueprint\Contracts;

interface ProvidesFilamentDiscovery
{
    public static function filamentDiscoveryPaths(): array;
}