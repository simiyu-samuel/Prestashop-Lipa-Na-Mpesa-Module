{extends file='page.tpl'}

{block name='page_content'}
<div class="container">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <form action="{$action_url}" method="post">
                <div class="form-group">
                    <label for="phone_number">Mpesa Phone Number</label>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" required>
                    <input type="hidden" name="order_id" value="{$order_id}">
                </div>
                <div>
                    <button type='submit' class='btn btn-primary'>PAY</button>
                </div>
            </form>
        </div>
    </div>
</div>
{/block}
