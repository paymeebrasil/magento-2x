require(
    [
        'uiComponent',
        'jquery',
        'Paymee_Core/js/util/jquery.mask.latest',
        'domReady!'
    ],
    function(Component, $) {
        'use strict';
        $(window).load(function() {
            var paymeeMethod = document.getElementById("paymee-method").value;
            if (paymeeMethod == "paymee_pix") {
                var uuid  = document.getElementById("uuid").value;
                var url   = document.getElementById("paymee-url").value;

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
        });
    }
);
