<?php
/**
 * 		物流表
 */
class contract_table extends table {

    protected $fields = [
        'id',
        'template_id',//模板id
        'name',//合同名称
        'file_url',//文档地址
        'status',//状态
        'create_time',//创建时间
    ];

    protected $pk='id';
}