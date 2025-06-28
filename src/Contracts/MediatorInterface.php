<?php

namespace BMCLibrary\Contracts;

interface MediatorInterface
{
    public function send(object $request);
}
