<?php
/**
 * 		物流表
 */
class order2_contract_table extends table {

    protected $fields = [
        'id',
        'order_no',
        'user_id',
        'template_id',
        'contract_id',
        'status',
        'transaction_id',
        'download_url',
        'viewpdf_url',
        'create_time',
    ];

    protected $pk='id';
}