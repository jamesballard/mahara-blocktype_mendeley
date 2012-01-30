{foreach from=$documents item=item}
<div class="mendeley_document" style="margin-bottom: 10px;">
    <a href="{$item['mendeley_url']}">
     {foreach from=$item['authors'] item=author}
     	{$author['surname']}, {$author['initials']}.
     {/foreach}
     ({$item['year']}).
     {if $item['type'] != "Journal Article"}
     <em>{$item['title']}.</em>
     {else}
     {$item['title']}.
     <em>{$item['published_in']}</em>
     {$item['volume']}
     ({$item['issue']}).
     {/if}
     
    </a>
    <div class="cb"></div>
</div>
{/foreach}