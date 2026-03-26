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
/* Mobile-first signup card style matching login: blue banner, white card */
body{ background: #ffffff; margin:0; font-family: myFont; }
#page_card{ max-width:420px; margin: 22px auto; background: #fff; border-radius:8px; box-shadow: 0 6px 18px rgba(0,0,0,0.12); overflow: hidden; }
.banner{ background: linear-gradient(180deg,#1877f2,#165fcf); height:120px; display:flex; align-items:center; justify-content:center; color:#fff; font-family: headFont; font-size:28px; }
.banner small{ display:block; font-family: myFont; font-size:13px; opacity:0.95; }
.card-body{ padding:18px 18px 26px; }
.form-row{ margin-bottom:12px; }
input[type=text], input[type=password]{ width:100%; padding:12px 14px; font-size:16px; border-radius:4px; border:1px solid #ddd; box-sizing:border-box; }
.primary-btn{ display:block; width:100%; background:#1a73e8; color:#fff; border:none; padding:12px 14px; font-size:16px; border-radius:6px; cursor:pointer; }
.secondary-link{ display:block; text-align:center; color:#1a73e8; margin-top:12px; text-decoration:none; }
.create-btn{ display:block; width:100%; background:#34a853; color:#fff; border:none; padding:10px 12px; font-size:15px; border-radius:6px; cursor:pointer; margin-top:10px; }

@media (max-width: 600px){
	#page_card{ margin: 12px; }
	.banner{ height:110px; font-size:22px; }
	.card-body{ padding:14px; }
}
</style>
<body>
	<div id="wrapper">
		<div id="page_card">
			<div class="banner">Walkie Talkie <small>Sign up</small></div>
			<div class="card-body">
				<div id="error" style="display:none;">Some Text</div>
				<form id="myform">
					<div class="form-row"><input type="text" name="username" placeholder="Username"></div>
					<div class="form-row"><input type="text" name="email" placeholder="Email"></div>
					<div class="form-row">Gender<br>
						<input type="radio" value="Male" name="gender_male"> Male
						<input type="radio" value="Female" name="gender_female"> Female
					</div>
					<div class="form-row"><input type="password" name="password" placeholder="Password"></div>
					<div class="form-row"><input type="password" name="password2" placeholder="Retype Password"></div>
					<div class="form-row"><input class="primary-btn" type="submit" value="Sign up" id="signup_button"></div>
				</form>
				<a class="secondary-link" href="login.php">Already have an account? Login!</a>
			</div>
		</div>
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