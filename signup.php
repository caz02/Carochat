<!DOCTYPE>
<html>
<head>
	<title>Walkie Talkie</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<style type="text/css">
	

	@font-face{

		font-family: headFont;
		src: url(ui/fonts/Bernadette/Bernadette.otf);

	}


	@font-face{

		font-family: myFont;
		src: url(ui/fonts/SinkinSans/SinkinSans-500Medium.otf);

	}


	#wrapper{

		max-width:900px;
		min-height: 500px; 
		margin:auto;
		color:grey;
		font-family: myFont;
		font-size: 13px; 

	}

	form{

		margin: auto;
		padding: 10px;
		width: 100%;
		max-width: 400px;
	}

	input[type=text], input[type=password], input[type=submit]{

		padding: 10px;
		margin: 10px;
		width: 98%;
		border-radius: 5px; 
		border: solid  1px grey;
	}

	input[type=submit]{
		margin:10px;
		width: 98%;
		height:40px;
		background-color: #2b5488;
		color: white;

	}

	input[type=radio]{

		transform: scale(1.2);
		cursor: pointer;
	}

		#header{

		background-color: #33476b;
		font-size: 50px;
		text-align: center;
		font-family:headFont;
		width:100%;
		color: white;


		}
		#error{

		text-align: center;
		padding: 0.5em;
		background-color: #f5be7f;
		color: white;
		display: none;
	}
</style>
<style type="text/css">
/* Mobile-only card/banner layout for small screens (signup page) */
@media (max-width: 600px) {
	body{ background:#f0f2f5; }
	#wrapper{
		max-width:420px;
		margin:18px auto;
		background:#fff;
		border-radius:8px;
		box-shadow:0 8px 26px rgba(0,0,0,0.12);
		overflow:hidden;
		color:#111;
	}

	#header{
		background: linear-gradient(180deg,#1877f2,#165fcf);
		color:#fff;
		font-family: headFont;
		font-size:22px;
		padding:18px 12px;
		text-align:center;
	}
	#header > div{ font-size:13px; color:rgba(255,255,255,0.95); font-family: myFont; }

	form{
		padding:16px;
		max-width:100%;
		box-sizing:border-box;
	}

	input[type=text], input[type=password]{
		width:100%;
		padding:12px 14px;
		font-size:16px;
		border-radius:6px;
		border:1px solid #e0e0e0;
		margin:8px 0;
		box-sizing:border-box;
		color:#111;
	}

	input[type=submit]{
		display:block;
		width:100%;
		padding:12px 14px;
		font-size:16px;
		background:#1a73e8;
		color:#fff;
		border:none;
		border-radius:6px;
		cursor:pointer;
		margin-top:8px;
	}

	a[href="login.php"]{
		display:block;
		text-align:center;
		margin:12px 0 6px 0;
		background:#34a853;
		color:#fff;
		padding:10px 12px;
		border-radius:6px;
		text-decoration:none;
	}
}
</style>
<body>
	<div id="wrapper">
		<div id="header">
		Walkie Talkie
		<div style="font-size:15px; font-family: myFont;">Signup</div>
		</div>
		<div id="error" style="">Some Text</div>

		<form id="myform">
			<input type="text" name="username" placeholder="Username"><br>
			<input type="text" name="email" placeholder="Email"><br>
		<div style="padding: 10px;" >
			
			<br>Gender<br>
			<input type="radio" value="Male" name="gender_male"> Male<br>
			<input type="radio" value="Female" name="gender_female"> Female<br>
		
			<input type="password" name="password" placeholder="Password"><br>
			<input type="password" name="password2" placeholder="Retype Password"><br>
			<input type="submit" value="Sign up" id="signup_button"><br>

			<br>
			<a href="login.php" style="display: block;text-align: center; text-decoration: none">
				Already have an account? Login!
			</a>
		</form>
		
	</div>

</body>
</html>

<script type="text/javascript">



	function _(element){

		return document.getElementById(element);

	}
	var signup_button = _("signup_button");
	signup_button.addEventListener("click",collect_data);

	function collect_data(){

		signup_button.disabled = true;
		signup_button.value = "Loading...Please Wait";

		var myform = _("myform");
		var inputs = myform.getElementsByTagName("INPUT");

		var data = {};
		for(var i = inputs.length - 1; i >= 0; i--){

			var key = inputs[i].name;

			switch(key){


				case "username":
					data.username = inputs[i].value;
					break;

				case "email":
					data.email = inputs[i].value;
					break;

				case "gender_male": 
				case "gender_female":
				case "gender_other":  
					if (inputs[i]. checked){
						data.gender = inputs[i].value;
					}
					break;

				case "password":
					data.password = inputs[i].value;
					break;

				case "password2":
					data.password2 = inputs[i].value;
					break;

			}
		}

		send_data(data,"signup");

	}

	function send_data(data,type){

		var xml = new XMLHttpRequest();
		// ensure cookies/session are sent for signup (will be set on successful signup)
		xml.withCredentials = true;


		xml.onload = function(){

			if(xml.readyState == 4 || xml.status == 200){

				handle_result(xml.responseText);
				signup_button.disabled = false;
				signup_button.value = "Signup";

			}

		}

		data.data_type = type;
		var data_string = JSON.stringify(data);
 

		xml.open("POST","api.php",true);
		xml.withCredentials = true;
		xml.send(data_string);

	
	}
 
	function handle_result(result){
alert(result);
		var data = JSON.parse(result);
		if(data.data_type == "info"){


 			window.location = "login.php";
		}else{

			var error = _("error");
			error.innerHTML = data.message;
			error.style.display = "block";

		}

	}
</script>