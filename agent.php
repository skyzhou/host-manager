<?php
error_reporting(0);

//是否校验权限
define('CHK',true);
define('WEL','******************************************<br /><br />***&nbsp;&nbsp;欢迎使用 agent.php ，建议使用Chrome浏览器<br /><br />***&nbsp;&nbsp;Sky (vece@vip.qq.com) <br /><br />***&nbsp;&nbsp;查看《手册》获取指令帮助，键入 config 设置界面<br /><br />******************************************');
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
	<title>安装agent.php</title>
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
<div id="header">欢迎使用agent.php，请填写如下信息，初始化系统权限的配置（请确保脚本所在目录具有写入文件的权限）<p class="error" id="error"></p></div>
<ul id="auth">
	<li><label>用户名</label><label>密码</label><label  class='dir'>可访问路径</label><label class="auth">写权限</label></li>
</ul>
<ul>
	<li><input type="button" onclick="Install.add();" value="增加" class="btn" /><input type="button" onclick="Install.exec();" value="完成" class="btn" /></li>
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
	function analyze($command,$dir,$file_name,$file_content){
		
		$dir=$dir=='$'?getcwd():$dir;
		chdir($dir);
		$dir=getcwd();
		$comand_arr=explode(" ",trim($command));
		$AUTH=parse_ini_file(AUTH,true);
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
					return array("dir"=>'$',"ret"=>"<br />请键入户名和密码登陆系统：login 您的用户名 您的密码","file_name"=>null,"file_content"=>null,user=>"agent");
			}
			$user_dir=dirname($AUTH[$_COOKIE['agent_user']]["dir"]);
		 
			 //目录鉴权
			$is_access=stripos(str_replace("\\","/",dirname($dir)),$user_dir)!==false;
			if(!$is_access&&$comand_arr[0]!="home"){
					return array("dir"=>$dir,"ret"=>"您没有该目录的读写权限",null,null);	
			}
			//写鉴权
			$is_write=$AUTH[$_COOKIE['agent_user']]["auth"]=="2";
			$need_write=array("mv","cp","mkdir","save","rm","");
			if(!$is_write&&in_array($comand_arr[0],$need_write))
			{
				return array("dir"=>$dir,"ret"=>"您没有该目录的写权限",null,null);	
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
				return array("dir"=>$dir,"ret"=>WEL,"file_name"=>null,"file_content"=>null,next_cmd=>"ls",user=>$is_login?$_COOKIE['agent_user']:"agent");
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
					return array("dir"=>$dir,"ret"=>"系统登陆成功，用户".$comand_arr[1],"file_name"=>null,"file_content"=>null,next_cmd=>"ls",user=>$comand_arr[1]);
				}
				else{
					return array("dir"=>'$',"ret"=>"用户名或密码错误",null,null);
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
							if($comand_arr[1])
							{
								$arr[]="<div>".(is_dir($file)?"<span class='dir detail' onclick=\"Shell.screen(event,'$file',1)\">$file/</span>":"<span    onclick=\"Shell.screen(event,'$file',0)\" class='detail'>$file</span>")."<span>--".date ("F d Y H:i:s.",filemtime($file))."</span><span>".ceil(filesize($file)/1000)."kb</span><br /></div>";
							}
							else{
								$arr[]=is_dir($file)?"<span class='dir' onclick=\"Shell.screen(event,'$file',1)\">$file/</span>":"<span onclick=\"Shell.screen(event,'$file',0)\">$file</span>";
							}
					}
				}
				$ret=iconv('gbk','utf-8',"<div>".implode("",$arr)."</div>");
				return array("dir"=>$dir,"ret"=>$ret,"file_name"=>null,"file_content"=>null,"file_list"=>$files);
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
				$file_content=$file_content==""?"输入内容……":$file_content;
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
				
				if(file_put_contents($dir."/".$file_name,$file_content)){
					return analyze("ls",$dir,null,null);
				}
				else{
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
					$ret="目录创建失败：权限不足";
				}
				break;
			}
			case "mv":{		
				if(is_file($comand_arr[1])&&is_file($comand_arr[1])&&rename($comand_arr[1],$comand_arr[2]))
				{
					return analyze("ls",$dir,null,null);}
				else{
					$ret="文件移动失败：权限不足";
				}
				break;
			}
			case "cp":{		
				if(is_file($comand_arr[1])&&is_file($comand_arr[1])&&copy($comand_arr[1],$comand_arr[2]))
				{
					return analyze("ls",$dir,null,null);}
				else{
					$ret="文件拷贝失败：权限不足";
				}
				break;
			}
			case "help":{
				return analyze("cat ".dirname(__FILE__)."/help.html",$dir,null,null);
				break;
			}
			case "exit":{
				setcookie("agent_user","",time()-1);
				setcookie("agent_pwd","",time()-1);
				return array("dir"=>'$',"ret"=>"已经退出登陆","file_name"=>null,"file_content"=>null,user=>"agent");
				break;
			}
			case "home":{
				$dir=is_dir($AUTH[$_COOKIE['agent_user']]["dir"])?$AUTH[$_COOKIE['agent_user']]["dir"]:getcwd();
				break;
			}
			case "console":{
				
			}
			//
			
			
		}
		return array("dir"=>$dir,"ret"=>$ret,"file_name"=>iconv('gbk','utf-8',$file_name),"file_content"=>$file_content);
	}
	
	echo JSON_encode(analyze(get('command'),get('dir'),get('file_name'),get('file_content')));
}
else{
header("Content-type: text/html; charset=utf-8"); 
?>
<!DOCTYPE HTML>
<html>
<head>
<?php 
$script=<<<SCRIPT
/**
 * @Util  
 * @author skyzhou
 * @version 0.1
 */
(function(scope){
    var $ = {},copy=$;
    $.$ = function(selector){
        return new $.dom.init(selector);
    };
	
    $.dom = {
		init: function(selector){
            if(typeof selector=="object"){
				this[0]=selector;
			}
			else if(selector=="body"){
				this[0]=document.getElementsByTagName("body")[0];
			}
			else{
				this[0]=document.getElementById(selector)||document.createElement("div");
			}
            return this;
        },
        html: function(str){
			if(arguments.length){
				this[0].innerHTML=str
				return this;
			}
			else{
				return this[0].innerHTML;
			}
		},
		
        show: function(opacity,sec){
            this.css("display","");
        },
		
        hide: function(opacity,sec){
            this.css("display","none");
        },
		
        css: function(name,value){
        	//参数纠正
			(function(){
				name=name.replace(/-([a-z])/g,function(all,letter){return letter.toUpperCase();});
			})();
			//写
			if(arguments.length==2){		
					this[0].style[name]=value;
				return this;
			}
			//读
			else{
				return  this[0].style[name];
			}
		},
        attr: function(name,value){
        	//写
			if(arguments.length==2){
					this[0].setAttribute(name,value);
					return this;
			}
			//读
			else{
				return  this[0].getAttribute(name);
			}
		},
        val: function(value){
			//写
			if (arguments.length == 1) 
			{
					this[0].value=value;
					return this;
			}
			else{	
					return this[0].value;	
			}
			
		},
		opacity:function(opacity){
				var v=this[0];
				v.style["opacity"]=v.style["MozOpacity"]=v.style["WebKitOpacity"]=opacity;
				v.style["filter"]="Alpha(opacity="+opacity*100+")"
			return this;
		},
		addEventListener:function(){
			var rat=document.createElement("div"),
			fx=[
				function(type,hdl){
					this[0].addEventListener(type,hdl,false);
				},
				function(type,hdl){
					this[0].attachEvent("on"+type,hdl);
				}
			],
			n=rat.addEventListener?0:1;
			return fx[n];
			
		}(),
		append:function(tag,attr){
			var elem=document.createElement(tag);
			for(var i in attr){
				elem[i]=attr[i];
			}
			this[0].appendChild(elem);
			return this;
		},
		remove:function(){
			this[0].parentNode.removeChild(this[0]);
		},
		focus:function(){
			this[0].focus();
			return this
		},
		children:function(tag){
			return this[0].getElementsByTagName(tag);
		},
		resize:function(){
			var h=parseInt(this[0].scrollHeight);
			this.css("height",h+"px");
		}
    };
    $.dom.init.prototype = $.dom;
	
	$.ajax={
		create:function(){
			var factories = [
					function() {return new ActiveXObject("Microsoft.XMLHTTP");}, 
					function() {return new XMLHttpRequest();}, 
					function(){return new ActiveXObject("Msxml2.XMLHTTP");} 
				];
				for (var i = 0; i < factories.length; i++) 
				{
					try 
					{
						if (factories[i]()) 
						{
							return factories[i];
						}
					} 
					catch (e) 
					{
						continue;
					}
				}
		}(),
		request:function(data){
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
				  
				  var xhr = this.create();
				  
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
							  
								  PARAM_DATA.success(xhr.responseText);
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
				  
			  }
		  }
	};
	$.cookie={
		set:function(name, value, expire)
			{
				var date=new Date(),path="/";
				date.setTime(date.getTime()+expire);
				document.cookie = name + "="+value
				+ ";expires=" + date.toGMTString();
			}
	};
	window[scope]=$;
})("SKY");
/**
 *@cache 
 *@author skyzhou
 *version 
 */
Cache={
	data:{
		auth:"agent@"+window.location.host+":",
		dir:"",
		file_type:"",
		file_name:"",
		file_content:"",
		next_cmd:"",
		file_list:null
	},
	set:function(data){
		for(var i in data){
			this.data[i]=data[i];
		}
	},
	get:function(name){
		return this.data[name]||"";
	}
}

/**
 *@socket 
 *@author skyzhou
 *version 
 */
var Socket={
	send:function(command,hdl){
		var url=window.location.pathname,param="cmd=1&command="+encodeURIComponent(command.replace(/\s{2,}/g," "))+"&dir="+Cache.get("dir")+"&file_type="+encodeURIComponent(Cache.get("file_type"))+"&file_name="+encodeURIComponent(Cache.get("file_name"))+"&file_content="+encodeURIComponent(Cache.get("file_content"));
		SKY.ajax.request({url:url,param:param,success:hdl});
	}
};
/**
 *@shell 
 *@author skyzhou
 *version 
 */
var Shell={
	//计数器
	count:-1,
	//注册
	sign:function(data){
		var config=SKY.$(data.config),monitor=SKY.$(data.monitor),command=SKY.$(data.command),label=SKY.$(data.label),timer,BD=SKY.$("body"),loading=SKY.$("loading"),WIN=window;
		//set cache
		Cache.set({config:config,monitor:monitor,command:command,label:label,loading:loading});
		
		SKY.$(data.label).html(Cache.get("auth")+Cache.get("dir"));
		//keydown
		command.addEventListener("keydown",function(evt){
				var isEditor=command.attr("editor")=="true",target=command[0];
				if(isEditor){
					if(evt.keyCode=="9"){
						if(target.createTextRange&&target.InsertPosition){
							target.InsertPosition.text = '\t';	
						}
						else if(window.getSelection){
							 var scrollTop = target.scrollTop, // 滚动的位置
							 start = target.selectionStart,// 当前光标所在位置
			  
							 pre = target.value.substr(0,target.selectionStart), // 光标之前的内容
							 next = target.value.substr(target.selectionEnd); // 光标之后的内容
							 target.value = pre + '\t' + next;						
			  
							 // 设置光标在插入点之后的位置
							 target.selectionStart = start + 1;
							 target.selectionEnd = start + 1;
							 target.scrollTop = scrollTop;
							 
						}
						//阻止默认行为
						try{evt.returnValue=false;evt.preventDefault()}catch(e){};
					}
					
					else if(evt.keyCode=="83"&&evt.ctrlKey){		
						//存储数据
						Cache.set({file_content:command.val()});
						//提交
						Shell.exec("save");
						//改变文本区域状态
						Shell.editor();
						//阻止默认行为
						try{evt.returnValue=false;evt.preventDefault()}catch(e){};
						
					}
					//quit
					else if((evt.keyCode=="81"&&evt.ctrlKey)||evt.keyCode=="27"){		
						Shell.editor();
						//阻止默认行为
						try{evt.returnValue=false;evt.preventDefault()}catch(e){};
					}
				}
				else{
					//13
					if(evt.keyCode=="13"){
						//config
						if(command.val()=="config"){
								config.css("display")=="none"?config.show():config.hide();
								Shell.monitor("");
						}
						else if(command.val()=="clean")
						{
								Shell.clean(true);
						}
						else
						{
							Shell.exec();	
						}
					}
					//38 up
					else if(evt.keyCode=="38"&&Shell.count>=0){
						command.val(Cache.get('history')[Shell.count]||'');
						Shell.count--;
					}
					//40 down
					else if(evt.keyCode=="40"&&Shell.count+1<Cache.get('history').length){		
						Shell.count++;
						command.val(Cache.get('history')[Shell.count]||'');
					}
					//table
					else if(evt.keyCode=="9"){
						Shell.complete();
						try{evt.returnValue=false;evt.preventDefault()}catch(e){};
					}
					//esc
					else if(evt.keyCode=="27"){
						Shell.exec("cd ../");
						try{evt.returnValue=false;evt.preventDefault()}catch(e){};
					}
				}
			});
		//blur
		command.addEventListener("blur",function(evt){
				var isEditor=command.attr("editor")=="true";
				var hdl=function(){
						command.focus();
						//将光标移到文本最后
						command.html(command.html());
				};
				isEditor?0:timer=setInterval(hdl,3000);
			});
		//focus
		command.addEventListener("focus",function(evt){
				clearInterval(timer);
			});
		BD.addEventListener("blur",function(evt){
				clearInterval(timer);
			});
		config.addEventListener("click",function(evt){
				clearInterval(timer);
			});

		//拖拽
		window.addEventListener('dragenter',function(evt){
			evt.preventDefault();
		});
		window.addEventListener('dragover',function(evt){
			evt.preventDefault();
		});
		
		window.addEventListener("drop",function(evt){
			evt.preventDefault();	
			var files=evt.dataTransfer.files;
			for(var i=0;i<files.length;i++){
				Shell.readfile(files[i]);
			};
		});
		this.exec("init");
	},
	//执行命令
	exec:function(cmd,upload){
		//判断类型，清掉文件类型
		upload?0:Cache.set({file_type:""});
		var command=cmd||Cache.get("command").val(),list=Cache.get('history')||[];
		//显示加载条
		Shell.loading("&#x52A0;&#x8F7D;&#x4E2D;&#x2026;&#x2026;");
		Socket.send(command,this.analyze);
		//保存命令
		cmd?0:list.push(command);
		Cache.set({history:list});
		this.count=Cache.get('history').length-1;
	},
	//返回数据分析
	analyze:function(data){
		Shell.loading("");
		data=eval('('+data+')');
		//写cache
		Cache.set(data);
		//如果有用户数据
		if(data.user){
			Cache.set({auth:data.user+"@"+window.location.host+":"});	
		}
		//if vim 编辑状态
		if(Cache.get("file_content")){
			//改变文本区域状态
			Shell.editor(true);
		}
		//显示
		else{
			Shell.monitor(Cache.get("ret"));
				
		}
		Cache.get("label").html(Cache.get("auth")+Cache.get("dir"));
		Shell.clean();
		
		//如果需要继续执行指令
		if(Cache.get("next_cmd")){
			var nextCMD=Cache.get("next_cmd");
			Cache.set({next_cmd:""});
			Shell.exec(nextCMD)
			
		}
	},
	monitor:function(str){
		Cache.get("monitor").append("li",{className:"last_cmd",innerHTML:Cache.get("auth")+Cache.get("dir")+"&nbsp;&nbsp;"+Cache.get("command").val()}).append("li",{innerHTML:str});
		Cache.get("command").val("").focus();
	},
	editor:function(isE){
		var command=Cache.get("command");
		if(isE){
			command.val(Cache.get("file_content")).attr("editor","true")[0].id="editor";
		}
		else{
			Cache.set({file_content:null});
			command.val("").attr("editor","")[0].id="command";
		}
	},
	//清屏
	clean:function(clean){
		var SIZE=5,monitor=Cache.get("monitor"),children=monitor.children("li");
		if(clean){
			monitor.html("");
			Shell.monitor("");
			config.hide();
		}
		else if(children.length>SIZE){
			for(var i=0;i<2;i++){
				SKY.$(children[0]).remove();
			}
		}
	},
	//快速打开目录或者文件
	screen:function(event,name,type){
		var evt=window.event||event,command=["cat "+name,"cd "+name];
		if(evt.ctrlKey){
			Shell.exec(command[type]);
		}
	},
	//读取本地文件
	readfile:function(file){
			var r=new FileReader();
			r.onload=function(evt){
				Shell.upload(file.name,file.type,evt.target.result);
			};
			if(file.type.indexOf("image")>-1||file.name.indexOf(".zip")>-1||file.name.indexOf(".rar")>-1)
			{
				r.readAsDataURL(file);
			}
			/*
			else if(file.name.indexOf(".zip")>-1)
			{
				r.readAsBinaryString(file);
			}
			*/
			else if(file.size){
				r.readAsText(file);
			}
			
			else{
				Shell.monitor(file.name+":&#x4E0D;&#x652F;&#x6301;&#x6587;&#x4EF6;&#x5939;&#x4E0A;&#x4F20;");
			}
	},
	//上传
	upload:function(name,file_type,content){
		Cache.set({file_name:name,file_type:file_type,file_content:content});
		Shell.exec("save",1);
	},
	config:function(flag){
		var ipts=Cache.get("config")[0].getElementsByTagName("input"),fx=SKY.cookie.set,t=3600*24*365,e=new Date().getTime()-1;
		if(flag){
			for(var i in ipts){
				fx(ipts[i].id,ipts[i].value,t);
			}
		}
		else{
			for(var i in ipts){
				fx(ipts[i].id,"",e);
			}
		}
		window.location.reload();
	},
	loading:function(str){
		Cache.get("loading").html(str||"");
	},
	//complete
	complete:function(){
		var command=Cache.get("command"),arr=command.val().split(" "),list=Cache.get("file_list"),last=arr.length-1;
		if(arr.length>1){
			var cmd=arr[last],r=new RegExp("^"+cmd+".*"),j=0,n;
			for(var i=0;i<list.length;i++){
				if(r.test(list[i])){
					j++;
					n=list[i];
				}
			}
			if(j==1){
				arr[last]=n;
			}
			command.val(arr.join(" "));
		}
		
	}
}
SCRIPT;
echo SPACE."<script type=\"text/javascript\">".$script."</script>";
//echo SPACE."<script type=\"text/javascript\" src=\"data:text/javascript;base64,".base64_encode($script)."\"></script>";
?>
<?php
//css config
function get_user_config($name,$d)
{
	if(isset($_COOKIE[$name])&&strlen($_COOKIE[$name])>1){
		return $_COOKIE[$name];
	}
	else{return $d;}
}
$user_config=array(
	get_user_config("conf_bg_color","#000000"),
	get_user_config("conf_ft_color","#FFFFFF"),
	get_user_config("conf_dir_color","#f7fa00"),
	get_user_config("conf_font_size","14px")
);
?>
<style type="text/css">body{font-size:<?php echo $user_config[3];?>;background:<?php echo $user_config[0];?>;color:<?php echo $user_config[1];?>;margin:0px;padding:10px;font-family:Verdana,Geneva,sans-serif;}
ul,li{margin:0px;padding-top:10px;padding:0px;list-style:none;clean:both;}#monitor{width:80%;}#monitor li span{margin-right:15px;float:left;}.dir{color:<?php echo $user_config[2];?>;}.detail{width:200px;display:inline-block;}.last_cmd{padding-top:10px;clear:both;}.ipt_area{clear:both;padding-top:10px;}.ipt_area label{float:left;}
textarea{background:<?php echo $user_config[0];?>;font-size:<?php echo $user_config[3];?>;padding:0px;font-family:Verdana,Geneva,sans-serif;color:<?php echo $user_config[1];?>;}
#command{margin:0px 0px 0px 0px;border:1px#694D14 solid;overflow:hidden;display:inline;width:30%;height:16px;}
#editor{display:block;margin:5px 0px 0px 5px;border:1px#694D14 solid;scrollbar-base-color:#694D14;width:65%;height:300px;word-wrap:break-word;}a{color:#f7fa00}</style>
<title>Agent</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
        <!--配置区域-->
        <div id="config" style="display:none;">
            <label>背景色:<input type="text" id="conf_bg_color" value="#000000"/></label>
            <label>前景色:<input type="text" id="conf_ft_color" value="#FFFFFF" /></label>
            <label>目录色:<input type="text" id="conf_dir_color" value="#f7fa00" /></label>
            <label>字号:<input type="text" id="conf_font_size" value="14px" /></label>
            <label><input type="button" value="保存" onclick="Shell.config(1);"/></label>
            <label><input type="button" value="默认" onclick="Shell.config(0);"/></label>
        </div>
        <!--显示区域-->
		<ul id="monitor"></ul>
		<!--命令区域-->
		<div class="ipt_area">
			<label id="auth"></label>&nbsp;&nbsp;<textarea rows="1" id="command"></textarea>
		</div>
        
        <!--加载区域-->
        <div id="loading">
        	
        </div>
		
		<script type="text/javascript">
			Shell.sign({config:"config",monitor:"monitor",command:"command",label:"auth"});		
		</script>
</body>
</html>
<?php echo SPACE; }?>
