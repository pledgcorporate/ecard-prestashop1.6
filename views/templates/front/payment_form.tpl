<div id="card-errors"></div>
<form action="{_MODULE_DIR_}pledg/validation" method="POST" class="hide" id="pledg_form">
	<div class="containerForm">
	    <div class="form-row">
	        <input type="text" size="20" maxlength="16" id="card-number" placeholder="{l s='Card Number' mod='pledg'}" autocomplete="off" class="card-number" />
	    </div>
	    <div class="form-row">
	        <input type="text"maxlength="2"  id="card-expiry-month" size="2" placeholder="MM" class="card-expiry-month"/>
	        <span> / </span>
	        <input type="text" maxlength="2" id="card-expiry-year" size="4" placeholder="YY" class="card-expiry-year"/>
	    </div>
	    <div class="form-row">
	        <input type="text" maxlength="3" id="card-cvc" size="4" placeholder="CVC" autocomplete="off" class="card-cvc" />
	    </div>
    </div>
    <br />
    <button type="submit" class="btn btn-primary">Valider</button>
</form>