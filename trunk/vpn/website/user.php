<?php

/**
 * @author henry
 * @copyright 2011
 */
 
require 'function.php'; 
require 'head.html';

$user = new user($db);
$list = $_GET['list'];


if($list == 'all'){
    $userlist = $user->all();
}else{
    $userlist = $user->online();
}

//print_r($userlist);

//print_r($online);

?>

   
  <div class="content">
    <h2>用户管理</h2>
    <h3><a href="user.php?list=all">查看所有用户</a>&nbsp; &nbsp;<a href="user.php">查看在线用户</a></h3>
    <?php if($list == 'all'){  ?>
    <table width="95%" border="1" cellspacing="0" cellpadding="0">
      <tr>
        <td width="6%" bgcolor="#CCCCCC">序号</td>
        <td width="9%" bgcolor="#CCCCCC">用户名</td>
        <td width="14%" bgcolor="#CCCCCC">注册时间</td>
        <td width="14%" bgcolor="#CCCCCC">累计流量</td>
        <td width="16%" bgcolor="#CCCCCC">到期时间</td>
        <td width="10%" bgcolor="#CCCCCC">当前状态</td>
        <td width="10%" bgcolor="#CCCCCC">累计连接</td>
        <td width="13%" bgcolor="#CCCCCC">&nbsp;</td>
        <td width="8%" bgcolor="#CCCCCC">操作</td>
      </tr>
      <?php
     // if(is_array($userlist))
      foreach($userlist as $username){
        
            $traffic = $user->traffic($username['username']);
            $traffic = $traffic['SUM( acctinputoctets + acctoutputoctets )'];
            $traffic = formatbytes($traffic);
            // get expiration date
            $expiration_date = $user->expiration_date($username['username']);
            $expiration_date = $expiration_date['value'];
            $expiration_date = change_date_view($expiration_date);
            // 获取连接累计时间
            $long_times = $user->long_times($username['username']);
            $long_times = $long_times['sum(acctsessiontime)'];
            if($long_times == null){
                $long_times = 0;
            }
            $long_times = sectohour($long_times);
            
            // 获取状态
            $status = $user->status($username['username']);
            
       ?>
      <tr>
        <td>&nbsp;</td>
        <td><?php echo $username['username']; ?></td>
        <td><?php echo $username['acctstarttime']; ?></td>
        <td><?php echo $traffic; ?></td>
        <td><?php echo $expiration_date; ?></td>
        <td><?php $online = $status ? "在线":"不在线";echo $online;?></td>
        <td><?php echo $long_times; ?></td>
        <td>&nbsp;</td>
        <td>&nbsp;&nbsp;<a href="do_user.php?action=detail&amp;username=<?php echo $username['username'];  ?>">详细</a></td>
      </tr>
      <?php  } ?>
    </table>  
    <?php } else {
    ?>
    <table width="95%" border="1" cellspacing="0" cellpadding="0">
      <tr>
        <td width="11%" bgcolor="#CCCCCC"><strong>序号</strong></td>
        <td width="16%" bgcolor="#CCCCCC"><strong>用户名</strong></td>
        <td width="32%" bgcolor="#CCCCCC"><strong>登录时间</strong></td>
        <td width="24%" bgcolor="#CCCCCC"><strong>累计流量</strong></td>
        <td width="17%" bgcolor="#CCCCCC"><strong>操作</strong></td>
      </tr>
      <?php
      if(is_array($userlist))
      foreach($userlist as $username){
            $traffic = $user->traffic($username['username']);
            $traffic = $traffic['SUM( acctinputoctets + acctoutputoctets )'];
            $traffic = formatbytes($traffic);
       ?>
      <tr>
        <td>&nbsp;</td>
        <td><?php echo $username['username']; ?></td>
        <td><?php echo $username['acctstarttime']; ?></td>
        <td><?php echo $traffic; ?></td>
        <td> <a href="do_user.php?action=kick&username=<?php echo $username['username'];  ?>">踢下线</a> &nbsp;&nbsp;<a href="do_user.php?action=detail&username=<?php echo $username['username'];  ?>">详细</a></td>
      </tr>
      <?php  } ?>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </table> 
  <?php } ?>
  
    <br />
    <table width="95%" border="1" cellspacing="0" cellpadding="0">
      <tr>
        <td width="25%"><strong>上一页</strong></td>
        <td width="51%">&nbsp;</td>
        <td width="24%"><strong>下一页</strong></td>
      </tr>
    </table>
	<br/>  
    
    
    <h3>添加新帐号</h3>
    <form action="do_user.php?action=add" method="post" enctype="application/x-www-form-urlencoded">
    <table width="95%" border="1" cellspacing="0" cellpadding="0">
      <tr>
        <td width="39%">用户名</td>
        <td width="61%"><input name="username" type="text" id="username" maxlength="20" /></td>
      </tr>
      <tr>
        <td>密码</td>
        <td><input name="password" type="text" id="password" maxlength="20" /></td>
      </tr>
            <tr>
        <td>邮箱</td>
        <td><input name="email" type="text" id="email" maxlength="20" /></td>
      </tr>
      <tr>
        <td>有效期</td>
        <td><input name="expiration" type="radio" id="radio" value="1" checked="checked" />
          一个月&nbsp; <input type="radio" name="expiration" id="radio2" value="2" />
二个月&nbsp; <input type="radio" name="expiration" id="radio3" value="3" />
三个月&nbsp; <input type="radio" name="expiration" id="radio4" value="6" />
六个月&nbsp; <input type="radio" name="expiration" id="radio5" value="12" />
一年</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td><input type="submit" name="button" id="button" value="提交" /></td>
      </tr>
    </table>
    </form>
    <p>&nbsp;</p>
    <!-- end .content --></div>

<?php
require 'foot.html';
?>