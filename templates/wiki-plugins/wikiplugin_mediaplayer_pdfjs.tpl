{strip}
    {if !$pdfJsAvailable}
        {remarksbox type="error" title="{tr}Missing Package{/tr}" close="n"}
            {if $oldPdfJsFileAvailable}
                {tr}Previous npm-asset/pdfjs-dist package has been deprecated.{/tr}<br/>
            {/if}
            {tr}To view pdf files Tiki needs npm-asset/pdfjs-dist-viewer-min package.{/tr}
            {tr}Please contact the Administrator to install it.{/tr}
        {/remarksbox}
    {elseif $source_link}
        <div class="iframe-container">
            <iframe src="{$source_link}" /></iframe>
        </div>
    {/if}
{/strip}
