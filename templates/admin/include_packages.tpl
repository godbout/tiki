{* $Id$ *}

{if isset($composer_output)}
    {remarksbox type="note" title="{tr}Note{/tr}"}

    {tr}The following list of changes has been applied:{/tr}<br />
        <pre>{$composer_output}</pre>
    {/remarksbox}
{/if}

{tabset name='tabs_admin-packages'}
    {tab name="{tr}Packages Installed{/tr}"}
        <br />
        <h4>{tr}Composer Packages{/tr} <small>{tr}status of the packages registered in the composer.json file{/tr}</small></h4>
        {if ! $composer_available}
            {remarksbox type="warning" title="{tr}Composer not found{/tr}"}
                {tr}Composer could not be executed, so the automated check on the packages can not be performed{/tr}
            {/remarksbox}
        {/if}
        <table class="table">
            <tr>
                <th>{tr}Package Name{/tr}</th>
                <th>{tr}Version Required{/tr}</th>
                <th>{tr}Status{/tr}
                <th>{tr}Version Installed{/tr}</th>
            </tr>
            {foreach item=entry from=$composer_packages_installed}
                <tr>
                    <td>{$entry.name}</td>
                    <td>{$entry.required}</td>
                    <td>
                        {if $entry.status == 'installed'}
                            {icon name='success' iclass='tips' ititle="{tr}Status{/tr}:{tr}Installed{/tr}"}
                        {elseif $entry.status == 'missing'}
                            {icon name='warning' iclass='tips' ititle="{tr}Status{/tr}:{tr}Missing{/tr}"}
                        {else}
                            &nbsp;
                        {/if}
                    </td>
                    <td>{$entry.installed|default:'&nbsp;'}</td>
                </tr>
            {/foreach}
            {if $composer_packages_missing}
                <tr>
                    <td colspan="4">
                        <h4>{tr}Looks like there are packages missing{/tr}</h4>
                        {tr}In the list above some of the packages could not be found, they are defined in the composer.json, but do not seem to be installed{/tr}
                        <h5>Fixing Manually</h5>
                        {tr}TODO: Manual install Instructions{/tr}
                        <h5>Fixing Automatically</h5>
                        {if $composer_available}
                            {tr}TODO: Automated install Instructions{/tr}
                            <form action="tiki-admin.php?page=packages" method="post">
                                <input type="hidden" name="redirect" value="0">
                                {ticket}
                                <button name="auto-fix-missing-packages" value="auto-fix-missing-packages">{tr}Fix Missing Packages{/tr}</button>
                            </form>
                        {else}
                            {tr}Composer was not detected, you need to follow the manual instructions{/tr}
                        {/if}
                    </td>
                </tr>
            {/if}
        </table>
    {/tab}
    {tab name="{tr}Install Other Packages{/tr}"}
        <br />
        <h4>{tr}Composer Packages{/tr} <small>{tr}this packages have been identified as a dependency of one or more features{/tr}</small></h4>
        <table class="table">
            <tr>
                <th>{tr}Package Name{/tr}</th>
                <th>{tr}Version{/tr}</th>
                <th>{tr}Licence{/tr}</th>
                <th>{tr}Required By{/tr}</th>
                <th>{tr}Install{/tr}</th>
            </tr>
            {foreach item=entry from=$composer_packages_available}
                <tr>
                    <td>{$entry.name}</td>
                    <td>{$entry.requiredVersion}</td>
                    <td><a href="{$entry.licenceUrl}">{if empty($entry.licence)}{tr}Not Available{/tr}{else}{$entry.licence}{/if}</a></td>
                    <td>{', '|implode:$entry.requiredBy}</td>
                    <td>
                        <form action="tiki-admin.php?page=packages&cookietab=2" method="post">
                            <input type="hidden" name="redirect" value="0">
                            {ticket}
                            <button name="auto-install-package" value="{$entry.key}">{tr}Install Package{/tr}</button>
                        </form>
                    </td>
                </tr>
            {/foreach}
        </table>
    {/tab}
{/tabset}