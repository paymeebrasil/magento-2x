require([ 'jquery'],function($){
 	$(window).load(function() {
 		var paymeeMethod    = document.getElementById("paymee-method").value;
 		console.log(paymeeMethod);

 		if (paymeeMethod == "PIX") {
 			console.log('chamou js success paymee pix');
 			var uuid            = document.getElementById("uuid").value;
 			var url             = document.getElementById("paymee-url").value;

 			var trigger = setInterval(function(){
 				$.ajax({
 					url: url,
 					type: "GET",
 					data: "uuid="+uuid,
 					success: function (data) {
 						console.log(data);
 						if (data == 'PAID') {
 							console.log('Pagamento aprovado!');
 							document.getElementById('container').style.display = 'none';
 							document.getElementById('alert').style.display = 'block';
 							clearInterval(trigger);
 						}
 					},
 				})
 			},6000);
 		}

 		var fiveMinutes = 60 * 10, display = document.querySelector('#tempo');
 		startTimer(fiveMinutes, display);

 		function startTimer(duration, display) {
 			var timer = duration, minutes, seconds;
 			setInterval(function () {
 				minutes = parseInt(timer / 60, 10);
 				seconds = parseInt(timer % 60, 10);

 				minutes = minutes < 10 ? "0" + minutes : minutes;
 				seconds = seconds < 10 ? "0" + seconds : seconds;

 				display.textContent = "Tempo restante "+ minutes + ":" + seconds;

 				if (--timer < 0) {
 					timer = duration;
 				}
 			}, 1000);
 		}
 	});
 });
