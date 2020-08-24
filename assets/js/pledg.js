var button = document.querySelector('#pledg-button')

new Pledg(button, {
    // the Pledg merchant id
    merchantId: 'mer_8ec99f9a-f650-4893-a4a3-16f20e16bb66',
    // the amount **in cents** of the purchase
    amountCents: 6500,

    title: 'test',
    // the email of the customer (optional - here, it is retrieved from a control on the page)
    email: document.querySelector('#customer-email').value,
    // reference of the purchase
    reference: 'order_123',
    // the name of the customer (optional, to improve anti-fraud)
    first_name: 'Eric',
    last_name: 'Tabarly',

    lang: 'en_GB',
    // the shipping address (optional, to improve anti-fraud)
    address: {
        street: '2, rue Frezier',
        city: 'Brest',
        zipcode: '29200',
        state_province: 'Bretagne',
        country: 'FR'
     },
    // the function which triggers the payment
    onSuccess: function (eCard) {
        document.querySelector('#card-number').value = eCard.card_number
        document.querySelector('#card-expiry-month').value = eCard.expiry_month
        document.querySelector('#card-expiry-year').value = eCard.expiry_year
        document.querySelector('#card-cvc').value = eCard.cvc
        //document.querySelector('#form').submit()
    },
    // the function which can be used to handle the errors from the eCard
    onError: function (error) {
        // see the "Errors" section for more a detailed explanation
    },
})

// The code below illustrates how the plugin can be reconfigured after its creation.
// Here, when the focus leaves the control #customer-email, the new email is
// set in the configuration of the plugin.
// All the parameters of the plugin can be reconfigured dynamically using the
// 'configure' method.
document.querySelector('#customer-email').addEventListener('blur', function(e) {
    pledgInstance.configure({
        email: document.querySelector('#customer-email').value
    })
})