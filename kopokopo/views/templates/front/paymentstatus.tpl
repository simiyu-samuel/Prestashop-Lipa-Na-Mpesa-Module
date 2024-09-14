{extends file='page.tpl'}

{block name='page_content'}
<div class="container">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div id="payment-status-message">
                <h2 class="alert alert-{$status}">{$msg}</h2>
            </div>
            <a href="{$link->getPageLink('index')}" class="btn btn-primary">Return to Home</a>
        </div>
    </div>
</div>

<script>
// document.addEventListener('DOMContentLoaded', function() {
//     // Retrieve the payment status from local storage
//     const status = localStorage.getItem('payment_status');
    
//     // Display the status
//     const statusMessageElement = document.getElementById('payment-status-message');
    
//     if (status === 'Success') { // Assuming the status stored in local storage is 'Success'
//         statusMessageElement.innerHTML = '<div class="alert alert-success">Your payment was successful!</div>';
//     } else {
//         statusMessageElement.innerHTML = '<div class="alert alert-danger">Payment failed. Please try again.</div>';
//     }

//     // Optionally, clear the status from local storage after displaying it
//     localStorage.removeItem('payment_status');
// });
</script>

{/block}
