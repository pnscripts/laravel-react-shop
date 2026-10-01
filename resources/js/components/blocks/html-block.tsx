/** Custom HTML entered by trusted staff (permission cms.html_block); shown as is. */
export function HtmlBlock({ html }: { html: string }) {
    return <div dangerouslySetInnerHTML={{ __html: html }} />;
}
