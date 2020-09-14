{foreach from=$payments_pledg key=name item=value}
    {if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
        <div class="row"><div class="col-xs-12{if version_compare($smarty.const._PS_VERSION_, '1.6.0.11', '<')} col-md-6{/if}">
    {/if}

    <form id="installment-form-{$value.id}" method="POST" action="{$value.redirectUrl}">
        <div class="payment_module pledg" id="payment_pledg_{$value.id}">
            <button id="installment-button-{$value.id}" type="button">{$value.titlePayment}</button>

            <div id="installment-container-{$value.id}"></div>
            <!--<div class="text-xs-center">
                <button id="btn-validation-{$value.id}" style="display:none;opacity: 0;" type="button" class="button btn btn-default button-medium" disabled>
                    <span>Valider le paiement</span>
                </button>
            </div>-->
        </div>
    </form>

    <!-- This script contains the code of the plugin -->
    <script src="https://s3-eu-west-1.amazonaws.com/pledg-assets/ecard-plugin/{$value.mode}/plugin.min.js"></script>

    <!-- This script contains the call to the plugin
         See the "Javascript" section below -->
    <script type="text/javascript">
        function getPledgButton{$value.id}() {
            return document.querySelector("#installment-button-{$value.id}")
        }

        function getPledgForm{$value.id}() {
            return document.querySelector("#installment-form-{$value.id}")
        }

        function getPledgContainer{$value.id}() {
            return document.querySelector("#installment-container-{$value.id}")
        }

        function addHiddenInput{$value.id}(form, label, value) {
            var hiddenInput = document.createElement("input")
            hiddenInput.setAttribute("type", "hidden")
            hiddenInput.setAttribute("name", label)
            hiddenInput.setAttribute("value", value)
            form.appendChild(hiddenInput)
        }

        var pledg{$value.id} = new Pledg(getPledgButton{$value.id}(), {
            containerElement:getPledgContainer{$value.id}(),
            paymentNotificationUrl: "{$value.notificationUrl}",
            signature: "{$value.signature}",
            externalCheckoutValidation: flase,
            showCloseButton: false,
            onCheckoutFormStatusChange: function(readiness){
                //document.querySelector("#btn-validation-1").disabled = !readiness;
            },
            // the function which triggers the payment
            onSuccess: function (resultpayment) {
                var form = getPledgForm{$value.id}()
                addHiddenInput{$value.id}(form, "merchantUid", "{$value.merchantUid}")
                addHiddenInput{$value.id}(form, "reference", "{$value.reference}")
                addHiddenInput{$value.id}(form, "transaction", resultpayment.transaction.id)
                form.submit()
            },
            // the function which can be used to handle the errors
            onError: function (error) {
                // see the "Errors" section for more a detailed explanation
                //document.querySelector("#btn-validation-{$value.id}").disabled = false;
            },
            onOpen: function() {
                //document.querySelector("#btn-validation-1").style = '';
            }
        });

        /*$('#btn-validation-{$value.id}').on('click', function () {
            pledg{$value.id}.validateCheckout();
            document.querySelector("#btn-validation-{$value.id}").disabled = true;
        });*/

    </script>

    {if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
        </div></div>
    {/if}
{/foreach}