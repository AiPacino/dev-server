<?php include template('header','admin');?>
	<body>
		<div class="fixed-nav layout">
			<ul>
				<li class="first">操作日志<a id="addHome" title="添加到首页快捷菜单">[+]</a></li>
				<li class="spacer-gray"></li>
				<li><a class="current" href="javascript:;"></a></li>
			</ul>
			<div class="hr-gray"></div>
		</div>
		<div class="content padding-big have-fixed-nav">
			<div class="tips margin-tb">
				<div class="tips-info border">
					<h6>温馨提示</h6>
					<a id="show-tip" data-open="true" href="javascript:;">关闭操作提示</a>
				</div>
				<div class="tips-txt padding-small-top layout">
					<p>- 系统默认关闭了操作日志</p>
					<p>- 开启操作日志可以记录管理人员的关键操作，但会轻微加重系统负担</p>
				</div>
			</div>
			<div class="hr-gray"></div>
            <div class="clearfix">
                <form>
                    <input type="hidden" name="m" value="admin">
                    <input type="hidden" name="c" value="log">
                    <input type="hidden" name="a" value="index">
                    <div class="order2-list-search clearfix">
                        <div class="form-box clearfix border-bottom-none" >
                            <div class="form-group form-layout-rank">
                                <span class="label">时间：</span>
                                <div class="box margin-none">
                                    <?php echo form::calendar('begin_time', !empty($_GET['begin_time']) ? $_GET['begin_time'] : '', array('format' => 'YYYY-MM-DD')) ?>
                                </div>
                            </div>
                            <div class="form-group form-layout-rank">
                                <span class="label">~</span>
                                <div class="box margin-none">
                                    <?php echo form::calendar('end_time', !empty($_GET['end_time']) ? $_GET['end_time'] : '', array('format' => 'YYYY-MM-DD')) ?>
                                </div>
                            </div>
                        </div>
                        <div class="form-box clearfix border-bottom-none" >
                            <?php echo form::input('select','option_id',$_GET['option_id'] ? $_GET['option_id'] : '0','操作功能','',array('css'=>'form-layout-rank','items' => $opreation_list))?>
                            <?php echo form::input('select', 'kw_type', $_GET['kw_type'] ? $_GET['kw_type'] : 'order_no', '搜索', '', array('css' => 'form-layout-rank', 'items' => $keywords_type_list)) ?>
                            <div class="form-group form-layout-rank">
                                <div class="box keywords margin-none">
                                    <input class="input keywords" name="keywords" placeholder="" tabindex="0" type="text" value="<?php echo!empty($_GET['keywords']) ? $_GET['keywords'] : '' ?>">
                                </div>
                            </div>
                        </div>
                        <input class="button bg-sub fl" value="查询" type="submit">
                    </div>
                </form>
            </div>
			<div class="table-work border margin-tb">
				<div class="border border-white tw-wrap">
					<a data-message="是否确定删除所选？" href="<?php echo url('del')?>" data-ajax='id'><i class="ico_delete"></i>删除</a>
					<div class="spacer-gray"></div>
				</div>
			</div>
			<div class="table resize-table check-table paging-table border clearfix">
				<div class="tr">
					<span class="th check-option" data-resize="false">
						<span><input id="check-all" type="checkbox" /></span>
					</span>
                    <span class="th" data-width="5">
						<span class="td-con">操作者ID</span>
					</span>
                    <span class="th" data-width="20">
						<span class="td-con">操作者IP</span>
					</span>
					<span class="th" data-width="10">
						<span class="td-con">操作者</span>
					</span>
					<span class="th" data-width="30">
						<span class="td-con">操作行为</span>
					</span>
					<span class="th" data-width="30">
						<span class="td-con">操作时间</span>
					</span>
<!--					<span class="th" data-width="10">-->
<!--						<span class="td-con">操作</span>-->
<!--					</span>-->
				</div>
				<?php foreach($log as $k=>$v):?>
				<div class="tr">
					<div class="td check-option"><input type="checkbox" name="id" value="<?php echo $v['id']?>" /></div>
                    <span class="td">
						<span class="td-con"><?php echo $v['user_id']?></span>
					</span>
                    <span class="td">
						<span class="td-con"><?php echo $v['action_ip']?></span>
					</span>
                    <span class="td">
						<span class="td-con"><?php echo $v['username']?></span>
					</span>
					<span class="td">
						<span class="td-con"><?php echo $v['remark']?></span>
					</span>
					<span class="td">
						<span class="td-con"><?php echo $v['dateline']?></span>
					</span>
<!--					<span class="td">-->
<!--						<span class="td-con ip" id='--><?php //$v['id'] ?><!--'>删除</span>-->
<!--					</span>-->
				</div>
				<?php endforeach;?>
				<div class="paging padding-tb body-bg clearfix">
					<ul class="fr">
						<?php echo $pages?>
					</ul>
				</div>
			</div>
		</div>
		<script src='http://whois.pconline.com.cn/ip.js'></script>
		<script>
			$('.table').resizableColumns();
			$(".paging-table").fixedPaging();
			$(function(){
				//查询IP
				$('.ip').on('mousemove',function(){
					if($(this).text() == '查看'){
						$(this).text($(this).attr('查询中'));
						labelIp($(this).attr('id'),$(this).attr('data-ip'));

					}
				})
			})
		</script>
<?php include template('footer','admin');?>
