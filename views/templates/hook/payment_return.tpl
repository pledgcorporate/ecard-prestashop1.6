{*
* 2020 Ginidev.com
*
*  @author Ginidev.com <gildas@ginidev.com>
*  @copyright  2020 Ginidev
*}

{if $status == 'ok'}
  <p>
    {l s='Your order on' mod='payzen'}&nbsp;<span class="bold">{$shop_name|escape:'html':'UTF-8'}</span> {l s='is complete.' mod='payzen'}
    <br /><br />
    {l s='We registered your payment of ' mod='payzen'}&nbsp;<span class="price">{$total_to_pay|escape:'html':'UTF-8'}</span>
    <br /><br />{l s='For any questions or for further information, please contact our' mod='payzen'}&nbsp;<a href="{$link->getPageLink('contact', true)|escape:'html':'UTF-8'}">{l s='customer support' mod='payzen'}</a>.
  </p>
{else}
  <p class="warning">
    {l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='bankwire'}
    <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='bankwire'}</a>.
  </p>
{/if}
