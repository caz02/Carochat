<!DOCTYPE>
<html>
<head>
	<title>Walkie Talkie</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<style type="text/css">
	

	@font-face{

		font-family: headFont;
		src: url(ui/fonts/Bernadette/Bernadette.ttf);

	}


	@font-face{

		font-family: myFont;
		src: url(ui/fonts/SinkinSans/SinkinSans-500Medium.otf);

	}

	body{
		background-image: url('ui/images/auth-bg.jpg');
		background-size: cover;
		background-position: center center;
		background-attachment: fixed;
		background-repeat: no-repeat;
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
		width: 98.5%;
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
/* Desktop-only background for login page. Place your desktop background image at ui/images/auth-bg.jpg */
@media (min-width: 601px) {
	body{
		background-image: url('ui/images/auth-bg.jpg');
		background-size: cover;
		background-position: center center;
		background-attachment: fixed;
		background-repeat: no-repeat;
	}
	/* keep the wrapper visually distinct on desktop */
	#wrapper{
		background: rgba(255,255,255,0.92);
		margin-top: 48px;
		border-radius: 8px;
		box-shadow: 0 10px 30px rgba(0,0,0,0.15);
	}
}
</style>
<style type="text/css">
/* Mobile adjustments */
@media (max-width: 600px) {
	#wrapper{
		max-width: 100%;
		padding: 8px;
	}

	form{
		max-width: 95%;
		padding: 12px;
	}

	form{
		margin-top: 20%;
	}
	input[type=text], input[type=password], input[type=submit]{
		padding: 14px;
		font-size: 18px;
		margin: 8px 6px;
		width: calc(100% - 12px);
	}

	input[type=submit]{
		height: 50px;
		font-size: 18px;
		border-radius: 6px;
	}

	#header{
		display: flex;
		justify-content: center;
		align-items: center;
		font-size: calc(6vw + 18px);
		padding: 28px 13px;
		margin: -13px;
	}

	#header > div{ font-size: 14px; }
}
</style>
<body>
	<div id="wrapper">

		<div id="header">
		Walkie Talkie
		</div>
		<div id="error" style="">Some Text</div>
		<form id="myform">
			<input type="text" name="email" placeholder="Email"><br>
			<input type="password" name="password" placeholder="Password"><br>
			<input type="submit" value="Login" id="login_button"><br>

			<br>
			<a href="signup.php" style="display: block;text-align: center; text-decoration: none">
				Don't have an account? Sign up!
			</a>
		</form>
		
	</div>

</body>
</html>

<script type="text/javascript">



	function _(element){

		return document.getElementById(element);

	}

	var login_button = _("login_button");
	login_button.addEventListener("click",collect_data);

	function collect_data(e){


		e.preventDefault();
		login_button.disabled = true;
		login_button.value = "Loading...Please Wait...";

		var myform = _("myform");
		var inputs = myform.getElementsByTagName("INPUT");

		var data = {};
		for (var i = inputs.length - 1; i >= 0; i--) {

			var key = inputs[i].name;

			switch(key){


				case "email":
					data.email = inputs[i].value;
					break;

				case "password":
					data.password = inputs[i].value;
					break;
			}
		}

		send_data(data,"login");

	}

	function send_data(data,type){


		var xml = new XMLHttpRequest();
		// ensure cookies/session are sent so login can set the session cookie
		xml.withCredentials = true;


		xml.onload = function(){

			if(xml.readyState == 4 || xml.status == 200){

				handle_result(xml.responseText);
				login_button.disabled = false;
				login_button.value = "Login";

			}

		}

		data.data_type = type;
		var data_string = JSON.stringify(data);


		xml.open("POST","api.php",true);
		xml.withCredentials = true;
		xml.send(data_string);

	
	}

	function handle_result(result)
{

		var data = JSON.parse(result);
		if(data.data_type == "info"){


 			window.location = "index.php";
		}else{

			var error = _("error");
			error.innerHTML = data.message;
			error.style.display = "block";

		}
}


</script>