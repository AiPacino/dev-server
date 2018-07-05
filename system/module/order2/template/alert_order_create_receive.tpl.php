<?php include template('header','admin');?>
<div id="dialog_box">
    <form action="<?php echo url('order2/receive/create_receive_confirmed')?>" method="POST" name="parcel_form">
	
	<div class="form-box border-bottom-none order-eidt-popup clearfix">
	    <div class="form-group">
		     是否确认生成收货单？
	    </div>
	</div>
	 <div class="form-box border-bottom-none order-eidt-popup clearfix"style="width: 100%;min-height: 55px; margin-top:10px;">
            <div>
                                        业务类型:
                <select style="border:1px solid #B8B8B8;margin-top: 5px;height:30px;width: 200px; " class="business_key" name="business_key">
                    <?php foreach($tab_list as $k => $v){ ?>
                        <option value="<?php echo $k ?>"><?php echo $v ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        
        	 <div class="form-box border-bottom-none order-eidt-popup clearfix"style="width: 100%;min-height: 55px; margin-top:10px;">
            <div>
                                    收货地址:
                <select style="border:1px solid #B8B8B8;margin-top: 5px;height:30px;width: 300px; " class="address_id" name="address_id">
                    <?php for($i=0;$i<count($address_list);$i++){ ?>
                        <option value="<?php echo $address_list[$i]['id'] ?>"><?php echo $address_list[$i]['address'] ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
	<input type="hidden" name="order_id" value="<?php echo $order_id?>">

	<div class="padding text-right ui-dialog-footer">
	<input type="submit" class="button bg-main" id="okbtn" value="确定" data-name="dosubmit" data-reset="false"/>
		<input type="button" class="button margin-left bg-gray" id="closebtn" value="取消"  data-reset="false"/>
	</div>
    </form>
</div>
<?php include template('footer','admin');?>
<script>
    $(function(){
        try {
            var dialog = top.dialog.get(window);
        } catch (e) {
            return;
        }
        dialog.title('生成收货单');
        dialog.reset();     // 重置对话框位置

        var parcel_form = $("form[name='parcel_form']").Validform({
            ajaxPost:true,
            beforeSubmit:function( curform ){
                $.message.start();
            },
            callback:function(ret) {
                if(ret.status == 1) {
                    $.message.end( ret.message, 2 );
                    setTimeout(function(){
                        window.top.main_frame.location.reload();
                    }, 2000);
                }else{
                    $.message.error( ret.message, 4 );
                }

                return false;
            }
        })

        $('#closebtn').on('click', function () {
            dialog.remove();
            return false;
        });
	})
</script>





