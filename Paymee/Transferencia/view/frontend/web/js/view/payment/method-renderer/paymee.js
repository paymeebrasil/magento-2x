/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'jquery'
    ],
    function (ko, Component,$) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Paymee_Transferencia/payment/banktransfer'
            },
            /*
            initObservable: function () {
                this._super()
                    .observe([
                        'allbank',
                        'activebankowner'
                    ]);
                return this;
            },*/
            getCode: function () {
                require(['jquery', 'Paymee_Transferencia/js/jquery.mask.latest'], function($){ 
                    $(document).ready(function() {
        
                        function TestaCPF(strCPF) {
                            var Soma;
                            var Resto;
                            Soma = 0;
                            if (strCPF == "00000000000") return false;
                            
                            for (i=1; i<=9; i++) Soma = Soma + parseInt(strCPF.substring(i-1, i)) * (11 - i);
                            Resto = (Soma * 10) % 11;
                        
                            if ((Resto == 10) || (Resto == 11))  Resto = 0;
                            if (Resto != parseInt(strCPF.substring(9, 10)) ) return false;
                        
                            Soma = 0;
                            for (i = 1; i <= 10; i++) Soma = Soma + parseInt(strCPF.substring(i-1, i)) * (12 - i);
                            Resto = (Soma * 10) % 11;
                        
                            if ((Resto == 10) || (Resto == 11))  Resto = 0;
                            if (Resto != parseInt(strCPF.substring(10, 11) ) ) return false;
                            return true;
                        }
                
                        $("#paymee_cpf").keydown(function(e){
                            if (e.keyCode !== 13 && e.keyCode !== 9) {
                                try {
                                    $("#paymee_cpf").unmask();
                                } catch (e) {}
                                
                                var tamanho = $("#paymee_cpf").val().length;
                                
                                if(tamanho < 11){
                                $("#paymee_cpf").mask("999.999.999-99");
                                } else if(tamanho >= 11){
                                $("#paymee_cpf").mask("99.999.999/9999-99");
                                }
                                
                                // ajustando foco
                                var elem = this;
                                setTimeout(function(){
                                    // mudo a posição do seletor
                                    elem.selectionStart = elem.selectionEnd = 10000;
                                }, 0);
                                // reaplico o valor para mudar o foco
                                var currentValue = $(this).val();
                                $(this).val('');
                                $(this).val(currentValue);
                            }
                        });
                        
                        $('#paymee_branch').mask('0000', {
                            onComplete: function(branch) {
                                $('#paymee_account').focus();
                            }
                        });
                        $('#paymee_account').mask("ZZZZZZZZZZZ0-A", {
                            reverse:true,
                            translation: {
                                    'Z': {
                                    pattern: /[0-9]/, optional: true
                                }
                            }
                        });
                    });
                });
                return 'paymee';
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'cpf': $('#paymee_cpf').val(),
                        'branch': $('#paymee_branch').val(),
                        'banco': $('#paymee_banco').val(),
                        'account': $('#paymee_account').val(),
                        'instructions': null,
                    }
                };
                
            },
            getBancos: function () {
                return [
                    { 'value': '001', 'banco': '001 - Banco do Brasil S.A'},
                    { 'value': '237', 'banco': '237 - Banco Bradesco S.A'},
                    { 'value': '341', 'banco': '341 - Banco Itaú-Unibanco S.A'},
                    { 'value': '341', 'banco': '341 - Depósito Identificado Itaú'},
                    { 'value': '104', 'banco': '104 - Caixa Econômica Federal'},
                    { 'value': '202', 'banco': '202 - Banco Original S.A'},
                    { 'value': '033', 'banco': '033 - Banco Santander Brasil S.A'},               
                    { 'value': '033', 'banco': '033 - Banco Santander S.A (Depósito em dinheiro)'},             
                    { 'value': '077', 'banco': '077 - Banco Inter S.A.'},
                    { 'value': '077', 'banco': '077 - Banco Inter S.A (BS2)'},
                ];
            },
            validate: function () {
                var $form = $('#' + this.getCode() + '-form');
                //this.getHash();
                return $form.validation() && $form.validation('isValid');
            },           
        });
    }
);