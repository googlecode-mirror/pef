<?php

require 'function.php';
require 'head.html';


$action = $_GET['action'];
$username = $_GET['username'];

$user = new user($db);

switch ($action) {
    case 'detail':
        $userinfo = $user->info($username);
        print_r($userinfo);
        break;
    case 'kick':
        if ($username == '') {
            echo "用户名为空";
            break;
        }
        $res = $user->kick($username);
        echo $res ? "成功踢掉用户":
        "失败";
        break;
    case 'change_expiration':
        break;
    case 'refresh':
        break;
	case 'disable':
		$res = $user->disable($username);
		echo $res ? "成功":"失败";
		break;
	case 'enable':
		$res = $user->enable($username);
		echo $res ? "成功":"失败";
		break;
	case 'add':
		$username = trim($_POST['username']);
		$password = trim($_POST['password']);
		$email = trim($_POST['email']);
		$expiration = trim($_POST['expiration']);
		
		if($expiration <= 0){
			$expiration = 0.1;
		}
		$time = time() + (86400 * $expiration * 30);
		$expiration = date("d M Y H:i:s",$time);
		$userinfo['username'] = $username;
		$userinfo['password'] = $password;
		$userinfo['expiration'] = $expiration;
		$userinfo['email'] = $email;
		$res = $user->add($userinfo);
		if(is_numeric($res)){
			echo '添加成功';
		}else{
			echo $res;
		}
		break;
	case 'delete':
		$res = $user->delete($username);
		//print_r($res);
		echo $res ? "成功":"失败";
		break;
    default:
        break;
}


?>

<?php
if ($action == 'detail') {
    $user->login_node($username)

?>
<div class="content">
    <h2>用户管理</h2>
    <h3><a href="user.php">查看在线用户</a>&nbsp; &nbsp;<a href="user.php?list=all">查看所有用户</a></h3>

<h3>用户详细信息    </h3>
    <table width="95%" border="1" cellspacing="0" cellpadding="0">
      <tr>
        <td width="25%">用户名</td>
        <td width="51%"><?php echo $username; ?></td>
      </tr>
      <tr>
        <td>用户邮箱</td>
        <td><?php echo $userinfo['email']; ?></td>
      </tr>
      <tr>
        <td>注册时间</td>
        <td><?php echo $userinfo['createtime']; ?></td>
      </tr>
      <tr>
        <td>累计使用时间</td>
        <td><?php echo sectohour($userinfo['logintimes']) ; ?> 小时</td>
      </tr>
      <tr>
        <td>累计使用流量</td>
        <td><?php echo formatbytes($userinfo['traffic']) ; ?></td>
      </tr>
      <tr>
        <td>到期时间</td>
        <td><?php echo change_date_view( $userinfo['expiration']) ; ?></td>
      </tr>
      <tr>
        <td>组别</td>
        <td><?php echo $userinfo['groupname']; ?></td>
      </tr>
      <tr>
        <td>登录次数</td>
        <td><?php echo $userinfo['logincounts']; ?></td>
      </tr>
       <tr>
        <td>当前状态</td>
        <td><?php echo $userinfo['status'] ? '在线' : '离线'; ?></td>
      </tr>
	<tr>
        <td>停用</td>
        <td><?php echo $userinfo['enabled'] == 1 ? '正常' : '停用 | (<a href="do_user.php?action=enable&username=' . $username . '">点击启用</a>)'; ?></td>
      </tr>
      <tr>
        <td>可选操作</td>
        <td><a href="do_user.php?action=kick&username=<?php echo $username; ?>">断线</a>&nbsp; <a href="do_user.php?action=disable&username=<?php echo
$username; ?>">禁用</a>&nbsp; <a href="do_user.php?action=change_expiration&username=<?php echo
$username; ?>">更改有效期</a>&nbsp; <a href="do_user.php?action=refresh&username=<?php echo
$username; ?>">更新状态</a> &nbsp; <a href="do_user.php?action=delete&username=<?php echo $username; ?>">删除账号</a></td>
      </tr>
    </table>
    </p>
    <!-- end .content -->
</div>

<?php
}
?>


<?php
require 'foot.html';

?>