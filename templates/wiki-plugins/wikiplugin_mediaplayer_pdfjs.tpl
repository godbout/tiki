{strip}
{if !$pdfJsAvailable}
    {remarksbox type=error title="{tr}Missing Package{/tr}" close="n"}
        {tr}To view pdf files Tiki needs npm-asset/pdfjs-dist package.{/tr}
        {tr}Please contact the Administrator to install it.{/tr}
    {/remarksbox}
{else}
    <div>
        <a class="btn btn-default btn-sm" data-role="button" id="mediaplayer-pdf-prev-{$mediaplayerId}">{tr}Previous{/tr}</a>
        <a class="btn btn-default btn-sm" data-role="button" id="mediaplayer-pdf-next-{$mediaplayerId}">{tr}Next{/tr}</a>
        <span class="float-sm-right small">{tr}Page{/tr}: <span id="mediaplayer-pdf-page-num-{$mediaplayerId}"></span> / <span id="mediaplayer-pdf-page-count-{$mediaplayerId}"></span></span>
    </div>
    <input type="hidden" class="mediaplayerDataUrl" id='mediaplayer-pdf-{$mediaplayerId}' value="{$url}">
    <div>
        <canvas id="mediaplayer-pdf-canvas-{$mediaplayerId}" style="border:1px solid gray" class="col-12"></canvas>
    </div>
{/if}
{/strip}
