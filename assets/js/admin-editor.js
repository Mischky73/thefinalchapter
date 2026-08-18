(() => {
  'use strict';

  const form = document.getElementById('article-form');
  if (!form) return;

  const uploadUrl = form.dataset.uploadUrl;
  const csrfToken = form.querySelector('input[name="csrf_token"]')?.value || '';

  const setStatus = (element, message, isError = false) => {
    if (!element) return;
    element.textContent = message;
    element.classList.toggle('is-error', isError);
  };

  const uploadImage = async (file, statusElement) => {
    if (!file) throw new Error('Bitte ein Bild auswählen.');
    if (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type)) {
      throw new Error('Erlaubt sind JPG, PNG, WebP oder GIF.');
    }
    if (file.size > 8 * 1024 * 1024) {
      throw new Error('Das Bild darf maximal 8 MB groß sein.');
    }
    setStatus(statusElement, 'Bild wird hochgeladen …');
    const body = new FormData();
    body.append('image', file);
    const response = await fetch(uploadUrl, {
      method: 'POST',
      headers: {'X-CSRF-Token': csrfToken, 'Accept': 'application/json'},
      body,
      credentials: 'same-origin'
    });
    let result;
    try {
      result = await response.json();
    } catch (_) {
      throw new Error('Der Server hat keine gültige Antwort gesendet.');
    }
    if (!response.ok || !result.ok || !result.url) {
      throw new Error(result.error || 'Der Upload ist fehlgeschlagen.');
    }
    setStatus(statusElement, 'Bild erfolgreich hochgeladen.');
    return result.url;
  };

  const uploadPanel = form.querySelector('[data-image-upload]');
  const featuredInput = document.getElementById('featured_image');
  const featuredFile = document.getElementById('featured_image_file');
  const preview = uploadPanel?.querySelector('[data-image-preview]');
  const placeholder = uploadPanel?.querySelector('[data-image-placeholder]');
  const featuredStatus = uploadPanel?.querySelector('[data-upload-status]');

  const updatePreview = (url) => {
    const hasImage = Boolean(url && url.trim());
    if (preview) {
      preview.hidden = !hasImage;
      if (hasImage) preview.src = url.trim();
    }
    if (placeholder) placeholder.hidden = hasImage;
    uploadPanel?.querySelector('.image-upload-preview')?.classList.toggle('is-empty', !hasImage);
  };

  const handleFeaturedFile = async (file) => {
    try {
      const url = await uploadImage(file, featuredStatus);
      featuredInput.value = url;
      updatePreview(url);
    } catch (error) {
      setStatus(featuredStatus, error.message || 'Upload fehlgeschlagen.', true);
    } finally {
      if (featuredFile) featuredFile.value = '';
    }
  };

  featuredFile?.addEventListener('change', () => handleFeaturedFile(featuredFile.files?.[0]));
  featuredInput?.addEventListener('input', () => updatePreview(featuredInput.value));
  ['dragenter', 'dragover'].forEach((eventName) => uploadPanel?.addEventListener(eventName, (event) => {
    event.preventDefault();
    uploadPanel.classList.add('is-dragging');
  }));
  ['dragleave', 'drop'].forEach((eventName) => uploadPanel?.addEventListener(eventName, (event) => {
    event.preventDefault();
    uploadPanel.classList.remove('is-dragging');
  }));
  uploadPanel?.addEventListener('drop', (event) => handleFeaturedFile(event.dataTransfer?.files?.[0]));

  const wysiwyg = form.querySelector('[data-wysiwyg]');
  const source = wysiwyg?.querySelector('.wysiwyg-source');
  const editor = wysiwyg?.querySelector('.wysiwyg-editor');
  const toolbar = wysiwyg?.querySelector('.wysiwyg-toolbar');
  const inlineFile = wysiwyg?.querySelector('.wysiwyg-inline-image');
  const inlineStatus = wysiwyg?.querySelector('[data-inline-upload-status]');
  let savedRange = null;

  const selectionInsideEditor = () => {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) return null;
    const range = selection.getRangeAt(0);
    return editor.contains(range.commonAncestorContainer) ? range.cloneRange() : null;
  };
  const rememberSelection = () => { savedRange = selectionInsideEditor() || savedRange; };
  const restoreSelection = () => {
    editor.focus();
    if (!savedRange) return;
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(savedRange);
  };
  const syncSource = () => { source.value = editor.innerHTML.trim(); };
  const escapeAttribute = (value) => String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');

  if (source && editor && toolbar) {
    editor.innerHTML = source.value;
    source.hidden = true;
    editor.hidden = false;
    toolbar.hidden = false;
    document.execCommand('defaultParagraphSeparator', false, 'p');

    editor.addEventListener('input', syncSource);
    editor.addEventListener('keyup', rememberSelection);
    editor.addEventListener('mouseup', rememberSelection);
    editor.addEventListener('blur', rememberSelection);

    toolbar.addEventListener('mousedown', (event) => event.preventDefault());
    toolbar.addEventListener('click', (event) => {
      const button = event.target.closest('button');
      if (!button) return;
      restoreSelection();
      const command = button.dataset.command;
      if (command) {
        document.execCommand(command, false, button.dataset.value || null);
        syncSource();
        rememberSelection();
        return;
      }
      if (button.dataset.action === 'link') {
        const url = window.prompt('Link-Adresse eingeben (https://…):', 'https://');
        if (url) document.execCommand('createLink', false, url.trim());
        syncSource();
        rememberSelection();
      } else if (button.dataset.action === 'inline-image') {
        rememberSelection();
        inlineFile?.click();
      }
    });

    inlineFile?.addEventListener('change', async () => {
      try {
        const url = await uploadImage(inlineFile.files?.[0], inlineStatus);
        restoreSelection();
        const escapedUrl = escapeAttribute(url);
        document.execCommand('insertHTML', false,
          `<figure><img src="${escapedUrl}" alt="" loading="lazy"><figcaption>Bildunterschrift</figcaption></figure>`);
        syncSource();
        rememberSelection();
      } catch (error) {
        setStatus(inlineStatus, error.message || 'Upload fehlgeschlagen.', true);
      } finally {
        inlineFile.value = '';
      }
    });

    form.addEventListener('submit', syncSource);
  }
})();
