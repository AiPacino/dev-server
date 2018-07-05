<?php


class credit_mechant_service extends service
{
    public function save()
    {
        $log->update($this);
        $credit->updateCredit($this);
    }
}