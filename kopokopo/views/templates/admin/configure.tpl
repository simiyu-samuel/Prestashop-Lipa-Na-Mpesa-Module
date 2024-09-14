{block name="content"}
    <div class="container">
        <form method="post" action="{$smarty.server.REQUEST_URI}">
            <fieldset>
                <legend>{l s='Kopokopo Configuration' mod='kopokopo'}</legend>
                
                <div class="form-group">
                    <label for="KOPOKOPO_TILL_NUMBER">{l s='Till Number' mod='kopokopo'}</label>
                    <input type="text" name="KOPOKOPO_TILL_NUMBER" id="KOPOKOPO_TILL_NUMBER" value="{$till_number|escape:'html':'UTF-8'}" class="form-control" required />
                </div>
                
                <div class="form-group">
                    <label for="KOPOKOPO_CLIENT_ID">{l s='Client ID' mod='kopokopo'}</label>
                    <input type="text" name="KOPOKOPO_CLIENT_ID" id="KOPOKOPO_CLIENT_ID" value="{$client_id|escape:'html':'UTF-8'}" class="form-control" required />
                </div>
                
                <div class="form-group">
                    <label for="KOPOKOPO_CLIENT_SECRET">{l s='Client Secret' mod='kopokopo'}</label>
                    <input type="text" name="KOPOKOPO_CLIENT_SECRET" id="KOPOKOPO_CLIENT_SECRET" value="{$client_secret|escape:'html':'UTF-8'}" class="form-control" required />
                </div>
                
                <div class="form-group">
                    <label for="KOPOKOPO_API_KEY">{l s='API Key' mod='kopokopo'}</label>
                    <input type="text" name="KOPOKOPO_API_KEY" id="KOPOKOPO_API_KEY" value="{$api_key|escape:'html':'UTF-8'}" class="form-control" required />
                </div>
                
                <div class="form-group">
                    <input type="submit" name="submit_kopokopo" value="{l s='Save' mod='kopokopo'}" class="btn btn-primary" />
                </div>
            </fieldset>
        </form>
        
        {if isset($confirmation)}
            <div class="alert alert-success">{$confirmation}</div>
        {/if}
    </div>
{/block}
