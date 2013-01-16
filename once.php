<?php
error_reporting(0);

//是否校验权限
define('CHK',true);
define('WEL','**************欢迎使用 once.php ，建议使用Chrome浏览器获得最佳使用效果[author:sky  mail:vece@vip.qq.com]**************');
define('SPACE','');
define('AUTH','auth.php');
/**
 获取POST数据	
 */
function get($name){
	return get_magic_quotes_gpc()?stripslashes($_REQUEST[$name]):$_REQUEST[$name];
}

//校验是否初始化过
$is_install=is_file('auth.php');

//安装
if(isset($_REQUEST['install'])){
	header("Content-type: text/html; charset=utf-8"); 
	//已经初始化过
	if($is_install){
			echo  SPACE."程序已经被安装过，如果想要重新安装，请先删除auth.php文件，<a href='".$_SERVER['PHP_SELF']."'>返回</a>";
	}
	
	//请求安装表单
	else if($_REQUEST['install']=='form'){
?>
		
<!DOCTYPE HTML>
<html>
<head>
	<title>安装once.php</title>
	<style type="text/css">
	body{
		margin:auto;
		margin-top:20px;
		width:800px;
		min-height:500px;
		border:1px solid #CEE7FF;
		border-radius:6px 6px 6px 6px;
		-moz-border-radius:6px;
		font-size:12px;
		font-family:"宋体";
	}
	#header{
		height:55px;
		background:#CEE7FF;
		margin-top:0px;
		padding:20px 0px 0px 44px;
		font-size:14px;
		color:#005E8A;
	}
	.error{
		font-size:12px;
		color:#FF0000;
	}
	li{
		list-style:none;
	}
	li label{
		padding:4px;
		width:150px;
		display:inline-block;
	}
	.dir{
		width:350px;
	}
	.auth{
		width:50px;
	}
	.btn{
		width:80px;
		height:30px;
		margin-right:20px;
		margin-left:5px;
	}
	</style>
</head>
<body>
<div id="header">欢迎使用once.php，请填写如下信息，初始化系统权限的配置（请确保脚本所在目录具有写入文件的权限）<p class="error" id="error"></p></div>
<ul id="auth">
	<li><label>用户名</label><label>密码</label><label  class='dir'>可访问路径</label><label class="auth">写权限</label></li>
</ul>
<ul>
	<li><input type="button" onClick="Install.add();" value="增加" class="btn" /><input type="button" onClick="Install.exec();" value="完成" class="btn" /></li>
</ul>
<script type="text/javascript">
	var Install={
		dir:"<?php echo str_replace("\\",'/',getcwd());?>",
		count:-1,
		add:function(){
			this.count++;
			var n=this.count;
			var li=document.createElement("li");
			li.innerHTML="<label><input type='text' id='user_"+n+"' /></label><label><input type='text' id='password_"+n+"'/></label><label class='dir'><input type='text' id='dir_"+n+"' value='"+this.dir+"' size='50'/></label><label class='auth'><input type='checkbox' id='auth_"+n+"' checked='true'/></label>";
			document.getElementById("auth").appendChild(li);
		},
		exec:function(){
			var arr=[],$=function(id){return encodeURIComponent(document.getElementById(id).value)},str=window.location.pathname;
			for(var i=0;i<=this.count;i++){
				$("user_"+i)?arr.push($("user_"+i)+"___"+$("password_"+i)+"___"+$("dir_"+i)+"___"+(document.getElementById("auth_"+i).checked?2:4)):0;
			}
			if(arr.length<1){
				this.error("请至少添加一个用户");
			}
			else{
				str+="?install=exec&auth="+arr.join("____");
				window.location.href=str;
			}
		},
		error:function(str){
			document.getElementById("error").innerHTML=str;
		}
	}
	Install.add();
</script>
</body>
</html>		
<?php
	echo SPACE;}
	//执行安装
	else if($_REQUEST['install']=='exec'){
		//已经初始化过
		if($is_install){
				echo  SPACE."程序已经被安装过，如果想要重新安装，请先删除auth.php文件，<a href='".$_SERVER['PHP_SELF']."'>返回</a>";
		}
		else{
			$config_info=get('auth');
			$config_info=explode("____",$config_info);
			$str=";<?php exit();?>\r\n";
			for($i=0;$i<count($config_info);$i++){
				$arr=explode('___',$config_info[$i]);
				$str.="[$arr[0]]\r\npassword=\"$arr[1]\"\r\ndir=\"$arr[2]\"\r\nauth=$arr[3]\r\n";
				if(file_put_contents(AUTH,$str)){
					echo SPACE."已经安装成功，<a href='".$_SERVER['PHP_SELF']."'>立即访问</a>";
				}
				else{
					echo  SPACE."权限不够，无法写入配置文件，请将目录：".getcwd()."的权限设置为可写，如：chmod -R 777 agent";
				}
			}
		}
		
	}
	
	exit();
	
}
//跳至安装
if(!$is_install&&!isset($_REQUEST['install'])){
	header('Location: '.$_SERVER['PHP_SELF'].'?install=form');
	exit();
}

//如果是指令
if(isset($_REQUEST['cmd'])){
	//
	
	/**
	 *遍历删除目录文件
	 */
	function rm_dir($dir){
		chdir($dir);
		$dir=getcwd();
		$files=scandir($dir);
		while(list($k,$file)=each($files))
		{
			if($file!="."&&$file!="..")
			{
				if(is_dir($file)){
					rm_dir($file);
					
				}
				else{
					unlink($file);	
				}
			}
		}
		chdir("../");
		rmdir($dir);
	}
	/**
	 *指令分析
	 */
	$AUTH=parse_ini_file(AUTH,true);
	function analyze($command,$dir,$file_name,$file_content){
		global $AUTH;
		$code=0;
		//操作是否成功标志
		$dir=$dir=='$'?getcwd():str_replace("\\","/",$dir);
		chdir($dir);
		$dir=getcwd();
		$comand_arr=explode(" ",trim($command));
		/**
		 *权限校验 begin
		 */
		 //用户权限
		 $is_login=array_key_exists($_COOKIE['agent_user'],$AUTH)&&md5($AUTH[$_COOKIE['agent_user']]["password"])==$_COOKIE['agent_pwd'];
		 //不需要权限的指令
		 $exception=array("login","cd","exit","init","help");
		 if(!in_array($comand_arr[0],$exception)){
			
			//登陆鉴权
			if(!$is_login){
					return array("code"=>2,"dir"=>'$',"ret"=>"请键入户名和密码登陆系统","file_name"=>null,"file_content"=>null,user=>"agent");
			}
			$user_dir=dirname($AUTH[$_COOKIE['agent_user']]["dir"]);
		 
			 //目录鉴权
			$is_access=stripos(str_replace("\\","/",dirname($dir)),$user_dir)!==false;
			if(!$is_access&&$comand_arr[0]!="home"){
					return array("code"=>1,"dir"=>$dir,"ret"=>"您没有该目录的读写权限",null,null);	
			}
			//写鉴权
			$is_write=$AUTH[$_COOKIE['agent_user']]["auth"]=="2";
			$need_write=array("mv","cp","mkdir","save","rm","");
			if(!$is_write&&in_array($comand_arr[0],$need_write))
			{
				return array("code"=>1,"dir"=>$dir,"ret"=>"您没有该目录的写权限",null,null);	
			}
			//文件类型鉴权
			
		 }
		 
		 /**
		 *权限校验 end
		 */
		/**
		 *新增指令一定要注意权限校验检查（是否需要登陆，是否具有目录访问权限，是否具有写权限）
		 */
		switch($comand_arr[0]){
			//初始化指令	
			case "init" :
			{
	
				$dir=$is_login&&is_dir($AUTH[$_COOKIE['agent_user']]["dir"])?$AUTH[$_COOKIE['agent_user']]["dir"]:"$";
				return array("code"=>0,"dir"=>$dir,"ret"=>WEL,"file_name"=>null,"file_content"=>null,next_cmd=>"ls",user=>$is_login?$_COOKIE['agent_user']:"agent");
				break;
			}
			//登录指令
			case "login" :
			{
				if(array_key_exists($comand_arr[1],$AUTH)&&$AUTH[$comand_arr[1]]["password"]==$comand_arr[2])
				{
					setcookie("agent_user",$comand_arr[1],time()+3600*24);
					setcookie("agent_pwd",md5($comand_arr[2]),time()+3600*24);
					$dir=is_dir($AUTH[$comand_arr[1]]["dir"])?$AUTH[$comand_arr[1]]["dir"]:getcwd();
					return array("code"=>0,"dir"=>$dir,"ret"=>"系统登陆成功，用户".$comand_arr[1],"file_name"=>null,"file_content"=>null,next_cmd=>"ls",user=>$comand_arr[1]);
				}
				else{
					return array("code"=>2,"dir"=>'$',"ret"=>"用户名或密码错误",null,null);
				}
			}
			//
			case "ls":
			{
				$files=scandir($dir);
				$arr=array();
				while(list($k,$file)=each($files))
				{
					if($file!="."&&$file!="..")
					{
							
						$arr[]=$file.'|'.(is_dir($file)?'1':'0');

					}
				}
				$ret=iconv('gbk','utf-8',implode("&",$arr));
				return array("code"=>0,"dir"=>$dir,"ret"=>$ret,"file_name"=>null,"file_content"=>null,"file_list"=>null);
				break;
			}
			case "cd":
			{
				return analyze("ls",$comand_arr[1],null,null);
				break;	
			}
			case "cat":{
				$pathinfo=pathinfo($comand_arr[1]);
				if(in_array($pathinfo['extension'],array("jpg","jpeg","png","gif"))){
					 $ret="<img src=\"data:image/".$pathinfo['extension'].";base64,".base64_encode(file_get_contents($comand_arr[1]))."\">";
				}
				else{
					$ret=htmlspecialchars(file_get_contents($comand_arr[1]));
				}
				break;	
			}
			case "vi":
			case "view";
			case "vim":
				$file_name=$comand_arr[1];
				$file_content=file_get_contents($comand_arr[1]);
				$file_content=$file_content==""?"\n":$file_content;
				break;
	
			case "save":{
				if(is_file($file_name)&&!is_writable($file_name))
				{
					chmod($file_name,0777);
				}
				//图片
				if(isset($_REQUEST['file_type'])&&(strpos($_REQUEST['file_type'],"mage")||strpos($file_name,".zip")||strpos($file_name,".rar")))
				{
					$pic_data=explode(",",$file_content);
					$file_content=base64_decode($pic_data[1]);
				}
				$exec_ret=file_put_contents($dir."/".$file_name,$file_content);	
				if($exec_ret){
					return analyze("ls",$dir,null,null);
				}
				else{
					$code=1;
					$file_content=null;
					$file_name=null;
					$ret="文件写入失败：权限不足";
				}
				break;
			}
			case "rm":{
				if($comand_arr[1]=="*"){
					$files=scandir($dir);
					while(list($k,$file)=each($files))
					{
						if($file!="."&&$file!="..")
						{
								analyze("rm $file",$dir,null,null);
						}
					}
				}
				else if(is_file($comand_arr[1])){
					$ret=unlink($comand_arr[1])?"":"文件删除失败：权限不足";
					$code=$ret===""?0:1;
				}
				else if(is_dir($comand_arr[1])){
					rm_dir($comand_arr[1]);
				}
				return analyze("ls",$dir,null,null);break;
			}
			case "mkdir":{
				if(mkdir($comand_arr[1]))
				{
					return analyze("ls",$dir,null,null);}
				else{
					$code=1;
					$ret="目录创建失败：权限不足";
				}
				break;
			}
			case "mv":{
				$exec_ret=rename($comand_arr[1],$comand_arr[2]);			
				if($exec_ret)
				{
					return analyze("ls",$dir,null,null);}
				else{
					$code=1;
					$ret="文件移动失败：权限不足";
				}
				break;
			}
			case "cp":{		
				if(copy($comand_arr[1],$comand_arr[2]))
				{
					return analyze("ls",$dir,null,null);}
				else{
					$code=1;
					$ret="文件拷贝失败：权限不足";
				}
				break;
			}
			case "exit":{
				setcookie("agent_user","",time()-1);
				setcookie("agent_pwd","",time()-1);
				return array("code"=>0,"dir"=>'$',"ret"=>"已经退出登陆","file_name"=>null,"file_content"=>null,user=>"agent");
				break;
			}		
		}
		return array('code'=>$code,"dir"=>$dir,"ret"=>$ret,"file_name"=>iconv('gbk','utf-8',$file_name),"file_content"=>$file_content);
	}
	
	echo JSON_encode(analyze(get('command'),get('dir'),get('file_name'),get('file_content')));
}
else{
header("Content-type: text/html; charset=utf-8"); 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>once.php</title>
		<style>
			.t_cls_file,.t_cls_fold,.t_cls_unfold,#tool{
				background: url(data:image/png;base64,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)  no-repeat ;
			}
			body{
				margin: 0px;
				padding: 0px;
				background: #f0f0f0;
			}
			#bar{
				padding: 0px 10px 25px 33px;
				margin:0px;
			}
			#tool{
				margin: 0px;
				padding:0px;
				height: 34px;
				background-position: 0 -221px;
				background-repeat:repeat-x;
			}
			#bar{
				color: #555555;
				font-size:12px;
				padding-left: 10px;
				padding-top:10px;
			}
			#bar label{
				margin-right: 20px;
				display: inline-block;
				min-width: 293px;
				overflow: visible;
			}
			#tree{
				float: left;
				padding: 10px 30px 10px 10px;
				margin: 0px;
				width: 250px;
				height:680px;
				background:#fff;
				overflow:auto;
				border: 2px solid #ccc;
				border-radius: 6px;
			}
			#main{
				padding:0px;
				margin-left:300px;
				height: 700px;
				border-radius: 6px;
				border:2px solid #99b4d1;
				border-right: 0px;
				background:#fff;
			}
			#view{
				background: #f0f0f0;
				height:660px;
				padding: 20px;
				font-size: 12px;
				color: #909090;
			}
			#editor{
				margin: 0px;
				border: 0px;
				border-left:8px solid #0099CC;
				border-radius: 4px;
				padding:5px;
				height:690px;
				width:95%;
				resize: none;
				outline:none;
				overflow-y:auto;
				overflow-x:hidden;
				word-wrap:break-word;
				
			}
			#ipt,#login{
				font-size:12px;
				border: 1px solid #999;
				background: #f0f0f0;
				position: absolute;
				top: -500px;
				left:-500px;
				opacity:0;
				box-shadow: 2px 2px 2px #999;
				-webkit-transition:opacity 0.4s ease-out 0s;
				padding:12px;
				border-radius: 6px;
			}
			#login div,#ipt div{
				text-align: center;
				padding: 8px;
			}
			#login span,#ipt span{
				padding: 4px 10px 4px 10px;
				cursor:pointer;
				border: 1px solid #99CCFF;
				border-radius: 4px;
				background: #F7FCFE;
			}
			#login span:hover,#ipt span:hover{
				border: 1px solid #99CCFF;
				background: #99CCFF;
			}
			#login{
			}
			#menu{
				margin: 0px;
				padding: 0px;
				font-size:12px;
				list-style: none;
				position: absolute;
				border: 1px solid #999;
				width: 230px;
				top: -500px;
				left:-500px;
				opacity:0;
				background: #f0f0f0;
				box-shadow: 2px 2px 2px #999;
				-webkit-transition:opacity 0.6s ease-out 0s;
			}
			#menu li{
				margin:3px;
				margin-left: 23px;
				border:1px solid #f0f0f0;
				border-bottom: 1px solid #E7E7E7;
				border-left:1px solid #E7E7E7;
				padding: 4px;
				padding-left:8px;
				cursor:pointer;
			}
			#menu li:hover{
				border:1px solid #CAE6FF;
				border-radius: 3px;
				background:#f6f5f5;
			
			}
			.t_cls_tool{
				display: inline-block;
				height: 15px;
				width: 55px;
				margin-left: 10px;
				padding:4px;
				margin-top:5px;
				cursor:pointer;
				font-size:12px;
				border:1px solid #ccc;
				border-radius:4px;
			}
			.t_cls_tool:hover{
				border:1px solid #69C;
			}
			
			.t_cls_tree ul{padding:0px;margin:0px;margin-left:16px;list-style:none;}
			.t_cls_tree label{cursor:pointer;padding-left:40px;}
			.t_cls_tree li{cursor:pointer;white-space:nowrap;border:1px solid #fff}
			.t_cls_tree li:hover{border-left:1px solid #d6e5f5}
			.t_cls_file{padding-left: 24px;height: 20px;}
			.t_cls_php{background-position:  0 0 }
			.t_cls_js{background-position:  0 -20px }
			.t_cls_css{background-position:  0 -40px}
			.t_cls_img{background-position:  0 -60px }
			.t_cls_txt{background-position:  0 -80px}
			.t_cls_unknown{background-position:  0 -100px }
			.t_cls_html{background-position:  0 -120px }
			/*.t_cls_save{background-position: 0 -142px}
			.t_cls_undo{background-position: 0 -162px}*/
			.t_cls_fold{background-position: 0 -185px}
			.t_cls_unfold{background-position: 0 -202px}
		</style>
		<script type="text/javascript">
        	/*
			 * @树形菜单
			 * @file	tree.js
			 * @author	skyzhou
			 * exmple	
			 */
			var $=function(id){
				return document.getElementById(id);
			};
			(function(scope,global){
				var Util={
					toJson:function(text){
						if(!text){
							return {};
						}
						var arr=text.split('&'),json={},folder={},file={};
						for(var i=0;i<arr.length;i++){
							var p=arr[i].split('|');
							p[1]*1?folder[p[0]]={}:file[p[0]]="";
						}
						json=folder;
						for(var p in file){
							json[p]='';
						}
						return json;
					}
				}
				global[scope]=Util;
			})('Util',this);
			(function(scope,global){
				var Ajax={
					post:function(data){
						try 
						  {
							 var PARAM_DATA={
									  url:"",
									  type:"POST",
									  param:null,
									  success:function(text){},
									  error:function(){}
							  };
							  for(var i in data)
							  {
								  PARAM_DATA[i]=data[i];
							  }
							  var xhr = new XMLHttpRequest();
							  var success=function(text)
							  {
								  var state = xhr.readyState; 															
								  if (state == 4) 
								  {
									  var status = xhr.status;
									  if(status == 200)
									  {
										  try
										  {
											  PARAM_DATA.success(eval('('+xhr.responseText+')'));
										  }
										  catch(e){
											  
										  }
									  }
									  else{
										  PARAM_DATA.error(status);
									  }
								  }
							  };
							  
							  xhr.onreadystatechange = success;
							  xhr.open(PARAM_DATA.type,PARAM_DATA.url,true);
							  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
							  xhr.send(PARAM_DATA.param);
						  } 
						  catch (e) 
						  {
							  console.log(e);
						  }
					}
				}
				global[scope]=Ajax;
			})('Ajax',this);
			(function(scope,global){
				function isEmpty(o){
					for(var i in o){
						return false;
					}
					return true;
				}
				function setPath(elem){
					var arr=[],pathname,path,that=elem;
					if(!(pathname=that.getAttribute('pathname'))){
						while(that&&(path=that.getAttribute('path'))!=='root'){
							path&&arr.unshift(path);
							that=that.parentNode;
						}
						pathname=arr.join('/');
						elem.setAttribute('pathname',pathname=arr.join('/'));
					}
					return pathname;
				}
				function getCls(file){
					var ct={
						'js':'js',
						'css':'css',
						'php':'php',
						'html':'html',
						'htm':'html',
						'txt':'txt',
						'png':'img',
						'gif':'img',
						'jpg':'img',
						'jpeg':'img',
						'bmp':'img',
						'unknown':'unknown'
					},extName=file.replace(/.*\.(\w+)/i,'$1');
					return 't_cls_'+(ct[extName]||ct['unknown']);
				}
				var Tree={
					render:function(json,parent,fd,fn,fresh){	
						fresh&&parent.removeChild(parent.lastChild);
						var ul=document.createElement("ul"),self=arguments.callee;
						for(var i in json){
							var li=document.createElement("li");
							li.setAttribute('path',i);
							//还有子节点
							if(typeof json[i]=="object"){
								var label=document.createElement("label"),sub=isEmpty(json[i])?1:0;
								label.innerHTML=i;
								label.className=(sub||fd)?"t_cls_fold":"t_cls_unfold";
								label.setAttribute('sub',sub);
								/*****
								 * 事件注册
								 * 目录
								 */
								label.addEventListener('click',function(evt){
									fn(setPath(this),1,this,evt);
								})
								label.addEventListener('contextmenu',function(evt){
									fn(setPath(this),1,this,evt);
									evt.preventDefault();
								})
								li.appendChild(label);
								self(sub?{'loading...':''}:json[i],li,true);
							}
							//叶子
							else{
								li.innerHTML=i;	
								li.className='t_cls_file '+getCls(i);
								/*****
								 * 事件注册
								 * 文件
								 */
								li.addEventListener('click',function(evt){
									fn(setPath(this),0,this,evt);
								})
								li.addEventListener('contextmenu',function(evt){
									fn(setPath(this),0,this,evt);
									evt.preventDefault();
								})
							}
							ul.appendChild(li);
						}
						ul.style.display=fd?"none":"";
						parent.appendChild(ul);
					},
					set:function(param){
						var f=document.createElement("div")/*document.createDocumentFragment()*/,
							json=param.json,
							wrap=param.wrap,
							dir=param.dir,
							hdl=param.hdl;
						this.render(json,f,false,hdl);
						f.className="t_cls_tree";
						f.setAttribute('path',dir);
						wrap.appendChild(f);
						wrap.setAttribute('path','root');
						hdl(dir,1);
					},
					fresh:function(ret,parent){
						var parent=parent||(Cmd.isDir?Tree.elem.parentNode.parentNode.parentNode:Tree.elem.parentNode.parentNode);
						Tree.render(Util.toJson(ret),parent,0,Page.hdl,1);
					},
					elem:null
				};
				global[scope]=Tree;
			})('Tree',this);
			
			(function(scope,global){
				var Cmd={
					send:function(cmd,fn){
						var that=this,arr=[
							'cmd=1',
							'command='+cmd.replace(/\s{2,}/g," "),
							'dir='+that.dir,
							'file_name='+that.file_name,
							'file_type='+that.file_type,
							'file_content='+encodeURIComponent(that.file_content)
						];
						Ajax.post({url:'once.php',success:function(data){
							if(data['code']*1){
								data['code']*1==2&&Page.box(1,'login',45,100);
								Page.error(data['ret'])
							}
							else{
								that.dir=data['dir'];
								fn(data);
							}
						},param:arr.join('&')})
					},
					dir:'',
					file_name:'',
					file_type:'',
					file_content:'',
					path:'',
					isDir:true
				};
				global[scope]=Cmd;
			})('Cmd',this);
			(function(scope,global){
				var path,action;
				var Tool={
					save:function(){
						Cmd.file_content=$('editor').value;
						Page.log('操作中……');
						Cmd.send('save',function(data){
							Page.log(Cmd.file_name+'保存成功');
							Tree.fresh(data.ret);
						});
					},
					undo:function(){
						Page.monitor('editor',0);
						Page.monitor('view',1);
						Page.monitor('tool_icon',0);
					},
					rm:function(){
						Cmd.isDir&&(Cmd.dir=Cmd.dir.replace(/(.+)[\\][^\\]+/,'$1'));
						Page.log('操作中……');
						Cmd.send('rm '+Cmd.path,function(data){
							Page.log('文件['+Cmd.path+']删除成功');
							Tree.fresh(data.ret);
						});
					},
					rename:function(){
						action='mv';
						Page.box(1,'ipt');
					},
					mv:function(val){
						Cmd.isDir&&(Cmd.dir=Cmd.dir.replace(/(.+)[\\][^\\]+/,'$1'));
						Page.log('操作中……');
						Cmd.send('mv '+Cmd.path+' '+val,function(data){
							Page.log('重命名为['+val+']成功');
							Tree.fresh(data.ret);
						});
					},
					newDir:function(val){
						if(val){
							Page.log('操作中……');
							Cmd.send('mkdir '+val,function(data){
								Page.log('添加目录['+val+']成功');
								Tree.fresh(data.ret,Cmd.isDir?Tree.elem.parentNode:Tree.elem.parentNode.parentNode);
							});
						}
						else{
							action='newDir';
							Page.box(1,'ipt');
						}
						
					},
					newFile:function(val){
						if(val){
							Cmd.file_name=val;
							Cmd.file_content='\n';
							Page.log('操作中……');
							Cmd.send('save',function(data){
								Page.log('添加文件['+val+']成功');
								Tree.fresh(data.ret,Cmd.isDir?Tree.elem.parentNode:Tree.elem.parentNode.parentNode);
							});
						}
						else{
							action='newFile';
							Page.box(1,'ipt');
						}
					},
					confim:function(){
						var val=$('f_name').value;
						Tool[action](val);
						Page.box(0,'ipt',-500,-500);
					},
					cancel:function(){
						Page.box(0,'ipt',-500,-500);
					},
					login:function(){
						var user=$('user').value,pwd=$('password').value;
						Page.log('正在登录……');
						Cmd.send('login '+user+' '+pwd,function(data){
								Page.log('登录成功');
								Page.box(0,'login',-500,-500);
								Cmd.send('ls',function(data){
								var dir=data.dir,parent=$("tree");
								parent.innerHTML='';
								Tree.set({json:Util.toJson(data.ret),wrap:parent,dir:dir,fold:false,hdl:Page.hdl});
							})
						});
					}
				}
				global[scope]=Tool;
			})('Tool',this);
			(function(scope,global){
				var elem,count=0,mX,mY;
				var Page={
					init:function(){
						var that=this;
						document.addEventListener('click',function(){
										that.menu(-500,-500,0);
						},true);
						$('f_name').addEventListener('keyup',function(evt){
										evt.keyCode=='13'&&Tool.confim();
						},true);
						window.addEventListener('dragenter',function(evt){
							evt.preventDefault();
						});
						window.addEventListener('dragover',function(evt){
							evt.preventDefault();
						});
						window.addEventListener('drop',function(evt){
							evt.preventDefault();
							var files=evt.dataTransfer.files;
							for(var i=0;i<files.length;i++){
								(function(){
									var index=i,file=files[i], r=new FileReader();
									r.onload=function(evt){
										Cmd.file_content=evt.target.result;
										Cmd.file_name=file.name;
										Cmd.file_type=file.type;
										Cmd.send('save',function(data){
											Page.log(Cmd.file_name+'保存成功');
											Tree.fresh(data.ret,Cmd.isDir?Tree.elem.parentNode:Tree.elem.parentNode.parentNode);
										});
									}
									if(/image/.test(file.type)){
										r.readAsDataURL(file);
									}
									else{
										r.readAsText(file);
									}
								})();
							}
						});
						Cmd.send('init',function(data){
							Page.log(data.ret);
							Cmd.send('ls',function(data){
								var dir=data.dir;
								Tree.set({json:Util.toJson(data.ret),wrap:$("tree"),dir:dir,fold:false,hdl:that.hdl});
							})
						});
					},
					hdl:function(path,isDir,target,evt){
						var self=arguments.callee,escapePath=path.replace(/\//g,'\\'),that=Page;
						Cmd.file_name=isDir?'':escapePath.replace(/.+[\\]([^\\]+)/,'$1');
						Cmd.dir=isDir?escapePath:escapePath.replace(/(.+)[\\][^\\]+/,'$1');
						Cmd.isDir=isDir;
						Cmd.path=function(){
							var elem=isDir?target.parentNode:target;
							return elem.getAttribute('path');
						}();
						elem&&(elem.style.backgroundColor='');
						Tree.elem=elem=target;
						elem.style.backgroundColor='#b4d5ff';
						
						Page.box(0,'ipt',-500,-500);
						if(evt.type=='contextmenu'){
							that.menu(evt.pageX,evt.pageY,1);
							that.swt(0);
						}
						else if(isDir&&target){
							var next=target.nextSibling,sub=target.getAttribute('sub')*1;
							isFold=next.style.display=="none";
							next.style.display=isFold?"":"none";
							target.className=isFold?"t_cls_unfold":"t_cls_fold";
							sub&&function(){
								that.log('正在读取目录['+escapePath+']信息……');
								that.get(path,function(data){
									next.parentNode.removeChild(next);
									target.setAttribute('sub','0');
									Tree.render(Util.toJson(data.ret),target.parentNode,0,self);
									that.log('目录['+escapePath+']信息读取完毕');
								});
							}();
							that.swt(0);
						}
						else if(!isDir){
							that.log('正在打开文件['+escapePath+']……');
							Cmd.send('vim '+path,function(data){
								that.log('文件['+escapePath+']打开完毕');
								$('editor').value=data['file_content'];
								that.swt(1);
							});
						}
						
						$('c_path').innerHTML=escapePath;
					},
					get:function(dir,fn){
						Cmd.send('cd '+dir,fn);
					},
					monitor:function(id,state){
						$(id).style.display=state?'':'none';
					},
					menu:function(x,y,o){
						var menu=$('menu');
						o&&(mX=x);
						o&&(mY=y);
						menu.style.top=parseInt(y)+'px';
						menu.style.left=parseInt(x)+'px';
						menu.style.opacity=o;
					},
					box:function(o,id,x,y){
						var box=$(id);
						x=x||mX;
						y=y||mY;
						box.style.top=parseInt(y)+'px';
						box.style.left=parseInt(x)+'px';
						box.style.opacity=o;
						if(o&&id=='ipt'){
							$('f_name').select();
						}
					},
					swt:function(flag){
						this.monitor('editor',flag);
						this.monitor('view',!flag);
						this.monitor('tool_icon',flag);
					},
					log:function(rec){
						var view=$('view'),rec='-> '+rec;
						count++
						if(count>24){
							view.removeChild(view.firstChild);
						}
						var p=document.createElement('p');
						p.innerHTML=rec;
						view.appendChild(p);
						$('c_log').innerHTML=rec;
					},
					error:function(msg){
						this.log(msg);
					}
				};
				global[scope]=Page;
			})('Page',this)

        </script>
    </head>
    <body>
        <div id='tool'>
        	<div id="tool_icon" style="display:none;">
        		<span class="t_cls_tool t_cls_save" onclick="Tool.save();" title="保存  Ctrl+S">保存编辑</span>
        		<span class="t_cls_tool t_cls_undo" onclick="Tool.undo();" title="撤销  Ctrl+W">退出编辑</span></div>
        </div>
        <div id="tree">
        </div>
        <div id="main">
        	<div id="view"></div>
        	<textarea id="editor" style="display:none;"></textarea>
        </div>
        <div id='bar'><label id='c_path'></label><span id="c_log"></span></div>
        <ul id='menu'>
        	<li onclick="Tool.newFile();">新建文件</li>
        	<li onclick="Tool.newDir();">新建目录</li>
        	<li onclick="Tool.rm();">删除</li>
        	<li onclick='Tool.rename();'>重命名</li>
        	<li>关闭</li>
        </ul>
        <div id="ipt">
        	<input type="text" placeholder="输入名称" id='f_name'/><div><span onclick="Tool.confim();">确  定</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span  onclick="Tool.cancel();">取  消</span></div>
        </div>
        <div id="login">
        	<div><input type="text" placeholder="用户名" id='user'/></div>
            <div><input type="text" placeholder="密码" id='password'></div>
            <div><span onclick="Tool.login();">登  录</span></div>
        </div>
        <script type='text/javascript'>
			Page.init();
        </script>
    </body>
</html>
<?php  }?>
