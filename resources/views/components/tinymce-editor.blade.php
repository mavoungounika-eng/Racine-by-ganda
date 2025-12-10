{{--
    Composant Éditeur WYSIWYG TinyMCE
    
    Usage:
    <x-tinymce-editor 
        name="content" 
        :value="$page->content ?? ''" 
        height="500"
    />
    
    Props:
    - name: string (required) - Nom du champ
    - value: string (optional) - Valeur initiale
    - height: integer (optional, default: 400) - Hauteur de l'éditeur en px
    - placeholder: string (optional) - Texte placeholder
--}}

@props([
    'name',
    'value' => '',
    'height' => 400,
    'placeholder' => 'Commencez à écrire...'
])

<div class="form-group">
    <textarea 
        id="{{ $name }}" 
        name="{{ $name }}" 
        rows="10"
        placeholder="{{ $placeholder }}"
        class="form-control @error($name) is-invalid @enderror"
    >{{ old($name, $value) }}</textarea>
    
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    tinymce.init({
        selector: '#{{ $name }}',
        height: {{ $height }},
        menubar: true,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount',
            'paste', 'textcolor', 'colorpicker'
        ],
        toolbar: 'undo redo | formatselect | ' +
            'bold italic forecolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | help | link image media | code preview fullscreen',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        language: 'fr_FR',
        branding: false,
        promotion: false,
        paste_as_text: false,
        images_upload_handler: function (blobInfo, progress) {
            return new Promise(function (resolve, reject) {
                var xhr = new XMLHttpRequest();
                xhr.withCredentials = false;
                xhr.open('POST', '{{ route("cms.api.upload-image") }}');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                
                xhr.upload.onprogress = function (e) {
                    progress(e.loaded / e.total * 100);
                };
                
                xhr.onload = function () {
                    if (xhr.status === 403) {
                        reject({ message: 'HTTP Error: ' + xhr.status, remove: true });
                        return;
                    }
                    
                    if (xhr.status < 200 || xhr.status >= 300) {
                        reject('HTTP Error: ' + xhr.status);
                        return;
                    }
                    
                    var json = JSON.parse(xhr.responseText);
                    
                    if (!json || typeof json.location != 'string') {
                        reject('Invalid JSON: ' + xhr.responseText);
                        return;
                    }
                    
                    resolve(json.location);
                };
                
                xhr.onerror = function () {
                    reject('Image upload failed due to a XHR Transport error. Code: ' + xhr.status);
                };
                
                var formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                
                xhr.send(formData);
            });
        },
        file_picker_callback: function(callback, value, meta) {
            if (meta.filetype == 'image') {
                var input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                
                input.onchange = function() {
                    var file = this.files[0];
                    var reader = new FileReader();
                    
                    reader.onload = function() {
                        var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                        var base64 = reader.result.split(',')[1];
                        var blobInfo = blobCache.create(file.name, file, base64);
                        blobCache.add(blobInfo);
                        callback(blobInfo.blobUri(), { title: file.name });
                    };
                    
                    reader.readAsDataURL(file);
                };
                
                input.click();
            }
        }
    });
});
</script>
@endpush

