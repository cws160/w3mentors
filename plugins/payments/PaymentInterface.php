<?php

/**
 * Payment Interface
 * 
 * @author Fatbit Technologies
 */
interface PaymentInterface
{

    public function initPayemtMethod(): bool;

    public function getChargeData();

    public function getProcessData(): array;

    public function getSuccessData(): array;

    public function getFailedData(): array;

    public function returnHandler(array $post): array;

    public function callbackHandler(array $post): array;
}
