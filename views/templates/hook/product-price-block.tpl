{if !empty($labels)}
<ul class="cw-cart-rules-labels">
    {foreach from=$labels item=label}
    {if $label}<li>{$label}</li>{/if}
    {/foreach}
</ul>
{/if}
