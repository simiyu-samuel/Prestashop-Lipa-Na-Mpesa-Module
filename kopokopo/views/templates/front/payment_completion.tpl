{extends file='page.tpl'}

{block name='page_content'}
<div class="container">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <h2 class="text-center">Payment Initiated</h2>
            {if isset($success)}
                <div class="alert alert-success">
                    {$success}
                </div>
            {/if}
            <p>You will receive a payment notification on your phone</p>
            <a href="{$p_url}"><button id="confirm-payment-btn" class="btn btn-success">Confirm Payment</button></a>
            
        </div>
        
        
    </div>
</div>



// <script>
// document.getElementById('confirm-payment-btn').addEventListener('click', async function() {
//     try {
//         const response = await fetch('/module/kopokopo/callback?order_id={$order_Id}');
        
        
//         if (!response.ok) {
//             throw new Error('Network response was not ok');
//         }
        
//         const data = await response.json();
//         // const c_data = json.encode(data, true);
//         console.log('Recieved Data:' data);
        
//         if (data && data.data && data.data.attributes) {
//             const status = data.data.attributes.status;

//             // Store the status in local storage
//             localStorage.setItem('payment_status', status);

//             // Redirect to the payment status page
//             window.location.href = '{$p_url}';
//         } else {
//             throw new Error('Invalid data received');
//         }
//     } catch (error) {
//         console.error('Error:', error);
//     }
// });

// </script>

{/block}