<?php

/**
 * @author henry
 * @copyright 2011
 */
require 'head.html';
require 'function.php'; 
$user = new user($db);
$node = new node($db);

$userlist = $user->online();
$all_node = $node->node_list();


//print_r($all_node);

?>

  <div class="content">
    <h2>系统状态    </h2>
    <h3>当前在线服务器状态</h3><table width="95%" border="1" cellspacing="0" cellpadding="0">
      <tr>
        <td width="9%" bgcolor="#CCCCCC"><strong>序号</strong></td>
        <td width="13%" bgcolor="#CCCCCC"><strong>地理位置</strong></td>
        <td width="15%" bgcolor="#CCCCCC"><strong>连线用户</strong></td>
        <td width="22%" bgcolor="#CCCCCC"><strong>外部IP地址</strong></td>
        <td width="17%" bgcolor="#CCCCCC">VPN IP</td>
        <td width="24%" bgcolor="#CCCCCC"><strong>状态</strong></td>
      </tr>
      <?php  
      $a = 1;
      foreach ($all_node as $node_list){
        $vpn_id = $node_list['vpn_id'];
        $node_online_count = $node->node_online_user_count($vpn_id);
      ?>
      <tr>
        <td><?php echo $a; ?></td>
        <td>&nbsp;<?php echo $node_list['location']; ?></td>
        <td>&nbsp;<?php echo $node_online_count; ?></td>
        <td>&nbsp;<?php echo $node_list['public_ip']; ?></td>
        <td>&nbsp;<?php echo $node_list['vpn_ip']; ?></td>
        <td><?php echo $node_list['status']; ?></td>
      </tr>
      <?php   
      $a++;
      }
      ?>
    </table>
    </p>
    <h3>当前在线用户</h3>
    <table width="95%" border="1" cellspacing="0" cellpadding="0">
      <tr>
        <td width="9%" bgcolor="#CCCCCC"><strong>序号</strong></td>
        <td width="13%" bgcolor="#CCCCCC"><strong>用户名</strong></td>
        <td width="27%" bgcolor="#CCCCCC"><strong>登录时间</strong></td>
        <td width="20%" bgcolor="#CCCCCC"><strong>累计流量</strong></td>
        <td width="14%" bgcolor="#CCCCCC">登录主机</td>
        <td width="14%" bgcolor="#CCCCCC"><strong>操作</strong></td>
      </tr>
      <?php
      if(is_array($userlist))
      foreach($userlist as $username){
            $traffic = $user->traffic($username['username']);
            $traffic = $traffic['SUM( acctinputoctets + acctoutputoctets )'];
            $traffic = formatbytes($traffic);
            $login_node = $user->login_node($username['username']);
           // print_r($login_node);
       ?>
      <tr>
        <td>&nbsp;</td>
        <td><?php echo $username['username']; ?></td>
        <td><?php echo $username['acctstarttime']; ?></td>
        <td><?php echo $traffic; ?></td>
        <td><?php echo $login_node['public_ip']; ?></td>
        <td><a href="do_user.php?action=kick&amp;username=<?php echo $username['username'];  ?>">踢下线</a> &nbsp;&nbsp;<a href="do_user.php?action=detail&amp;username=<?php echo $username['username'];  ?>">详细</a></td>
      </tr>
      <?php  } ?>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </table> 
    </p>
    <h3>系统负载</h3>
    <p><img src="eth0-day.png" width="500" height="135" /></p>
    <p><img src="eth0-day.png" width="500" height="135" /></p>
    <p>&nbsp;</p>
    <h3>系统情况</h3>
    <table width="95%" border="1" cellspacing="0" cellpadding="0">
      <tr>
        <td width="39%" bgcolor="#CCCCCC"><strong>项目</strong></td>
        <td width="61%" bgcolor="#CCCCCC"><strong>值</strong></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </table>
    <p>&nbsp;</p>
    <!-- end .content --></div>



<?php
require 'foot.html';
?>