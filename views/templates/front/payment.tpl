{assign var=langs value=['fr'=>'fr_FR', 'en'=>'en_GB', 'de' => 'de_DE', 'es' => 'es_ES', 'it' => 'it_IT', 'nl' => 'nl_NL']}
{if $lang_iso|array_key_exists:$langs}
    {assign var=lang value=$langs[$lang_iso]}
{else}
    {assign var=lang value=$langs['en']}
{/if}

{foreach from=$payments_pledg key=name item=value}
    {if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
        <div class="row"><div class="col-xs-12{if version_compare($smarty.const._PS_VERSION_, '1.6.0.11', '<')} col-md-6{/if}">
    {/if}

    <form id="installment-form-{$value.id}" method="POST" action="{$value.redirectUrl}">
        <div class="payment_module pledg" id="payment_pledg_{$value.id}">
            <button id="installment-button-{$value.id}" type="button" class="{if $value.icon}has-icon{/if}">
                {if $value.icon}
                    <img src="{$value.icon}">
                {/if}
                {$value.titlePayment}
            </button>

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

        var isError{$value.id} = false;
        var messageError{$value.id} = '';
        var isOpen{$value.id} = false;

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

        function displayError{$value.id}() {
            getPledgContainer{$value.id}().remove();

            var alertDiv = document.createElement("div")
            alertDiv.setAttribute("class", "alert alert-danger")
            alertDiv.textContent = messageError{$value.id}
            document.querySelector("#payment_pledg_{$value.id}").appendChild(alertDiv)

        }

        var pledg{$value.id} = new Pledg(getPledgButton{$value.id}(), {
            containerElement:getPledgContainer{$value.id}(),
            paymentNotificationUrl: "{$value.notificationUrl}",
            {if $value.signature}
                signature: "{$value.signature}",
            {else}
                merchantUid: "{$value.merchantUid}",
                title: "{$value.title}",
                reference: "{$value.reference}",
                amountCents: "{$value.amountCents}",
                currency: "{$value.currency}",
                civility: "{$value.civility}",
                firstName: "{$value.firstName}",
                lastName: "{$value.lastName}",
                email: "{$value.email}",
                address: {$value.address|json_encode},
                phoneNumber: "{$value.phoneNumber}",
            {/if}
            externalCheckoutValidation: false,
            showCloseButton: false,
            lang: "{$lang}",
            onCheckoutFormStatusChange: function(readiness){
                //document.querySelector("#btn-validation-1").disabled = !readiness;
            },
            // the function which triggers the payment
            onSuccess: function (resultpayment) {
                var form = getPledgForm{$value.id}();
                addHiddenInput{$value.id}(form, "merchantUid", "{$value.merchantUid}");
                addHiddenInput{$value.id}(form, "reference", "{$value.reference}");
                addHiddenInput{$value.id}(form, "transaction", resultpayment.purchase.reference);
                form.submit();
                addLog('Pledg Form Success Paid. Return value : ' . JSON.stringify(resultpayment), 'success', 'PledgPayment', {$value.id});
            },
            // the function which can be used to handle the errors
            onError: function (error) {
                isError{$value.id} = true;
                messageError{$value.id} = error.message;

                addLog('Error Pledg Payment Form : ' + error.message, 'error', 'PledgPayment', {$value.id});

                if (isOpen{$value.id}) {
                    displayError{$value.id}();
                }

                // see the "Errors" section for more a detailed explanation
                //document.querySelector("#btn-validation-{$value.id}").disabled = false;
            },
            onOpen: function() {
                addLog('Open Pledg Payment Form', 'success', 'PledgPayment', {$value.id});
                isOpen{$value.id} = true;
                if (isError{$value.id}) {
                    displayError{$value.id}();
                }
            }
        });

    </script>

    {if version_compare($smarty.const._PS_VERSION_, '1.6', '>=')}
        </div></div>
    {/if}
{/foreach}

<script>
    function addLog(msg, typeLog, classObject = '', idObject = '') {
        var urlAjax = "{$link->getModuleLink('pledg', 'log', [], true)|escape:'html'}";
        $.ajax({
            url : urlAjax,
            method: 'POST',
            data: {
                message: msg,
                type: typeLog,
                class: classObject,
                id: idObject
            }
        });
    }
</script>