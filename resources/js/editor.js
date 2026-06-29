// Visual (WYSIWYG) editor for blog posts. Loaded only on the blog create/edit
// pages. Wraps a hidden <textarea name="content"> so the backend still receives
// markdown and nothing else in the pipeline changes.

import Editor from '@toast-ui/editor';
import '@toast-ui/editor/dist/toastui-editor.css';

document.addEventListener('DOMContentLoaded', () => {
    const mount = document.querySelector('#markdown-editor');
    const textarea = document.querySelector('#content');
    if (!mount || !textarea) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const editor = new Editor({
        el: mount,
        height: '560px',
        initialEditType: 'wysiwyg',
        previewStyle: 'vertical',
        initialValue: textarea.value || '',
        usageStatistics: false,
        toolbarItems: [
            ['heading', 'bold', 'italic', 'strike'],
            ['hr', 'quote'],
            ['ul', 'ol'],
            ['table', 'link'],
            ['image', 'code', 'codeblock'],
        ],
        hooks: {
            // Upload pasted/inserted images through the existing media endpoint
            addImageBlobHook: async (blob, callback) => {
                try {
                    const form = new FormData();
                    form.append('files[]', blob);
                    form.append('folder', 'blog');

                    const res = await fetch('/admin/media', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        body: form,
                    });
                    const data = await res.json();
                    const url = data?.files?.[0]?.url;
                    if (url) {
                        callback(url, blob.name || 'image');
                    } else {
                        console.error('Image upload failed', data);
                    }
                } catch (e) {
                    console.error('Image upload error', e);
                }
            },
        },
    });

    // Keep the hidden textarea in sync so the form submits markdown
    const sync = () => { textarea.value = editor.getMarkdown(); };
    editor.on('change', sync);

    const formEl = textarea.closest('form');
    if (formEl) {
        formEl.addEventListener('submit', sync);
    }
});
