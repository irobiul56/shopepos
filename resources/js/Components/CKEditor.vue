<script setup>
import { ref, onMounted, onBeforeUnmount, watch, defineEmits, defineProps } from 'vue';

const props = defineProps({
    modelValue: String,
    config: Object,
});

const emit = defineEmits(['update:modelValue']);
const editorId = `quill-${Math.random().toString(36).substr(2, 9)}`;
const editorInstance = ref(null);
const editorContainer = ref(null);

const defaultConfig = {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            [{ 'font': [] }],
            [{ 'size': ['small', false, 'large', 'huge'] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'script': 'sub'}, { 'script': 'super' }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            [{ 'align': [] }],
            ['blockquote', 'code-block'],
            ['link', 'image', 'video'],
            ['clean'],
            
        ]
    },
    placeholder: 'Compose an epic...',
    readOnly: false
};

const initializeEditor = () => {
    if (window.Quill) {
        const config = { ...defaultConfig, ...props.config };
        
        // Create editor
        editorInstance.value = new Quill(`#${editorId}`, config);
        
        // Set initial content
        if (props.modelValue) {
            editorInstance.value.root.innerHTML = props.modelValue;
        }
        
        // Handle text change
        editorInstance.value.on('text-change', function() {
            const html = editorInstance.value.root.innerHTML;
            emit('update:modelValue', html === '<p><br></p>' ? '' : html);
        });
        
        // Handle selection change for toolbar updates
        editorInstance.value.on('selection-change', function(range) {
            if (range) {
                // Selection is active
                console.log('Selection active:', range);
            }
        });
    }
};

// Watch for modelValue changes from parent
watch(() => props.modelValue, (newValue) => {
    if (editorInstance.value && newValue !== editorInstance.value.root.innerHTML) {
        const selection = editorInstance.value.getSelection();
        editorInstance.value.root.innerHTML = newValue || '';
        if (selection) {
            editorInstance.value.setSelection(selection);
        }
    }
});

onMounted(() => {
    if (!window.Quill) {
        // Load Quill CSS
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdn.quilljs.com/1.3.7/quill.snow.css';
        document.head.appendChild(link);
        
        // Load Quill JS
        const script = document.createElement('script');
        script.src = 'https://cdn.quilljs.com/1.3.7/quill.js';
        script.onload = initializeEditor;
        document.head.appendChild(script);
    } else {
        initializeEditor();
    }
});

// Cleanup Quill instance
onBeforeUnmount(() => {
    if (editorInstance.value) {
        // Quill doesn't have a destroy method, but we can remove the element
        const container = editorInstance.value.container;
        if (container && container.parentNode) {
            container.parentNode.removeChild(container);
        }
        editorInstance.value = null;
    }
});
</script>

<template>
    <div>
        <div :id="editorId" ref="editorContainer"></div>
    </div>
</template>

<style scoped>
/* Quill container styling */
:deep(.ql-container) {
    font-family: 'Helvetica Neue', Arial, sans-serif;
    font-size: 13px;
    height: 300px;
    border-bottom-left-radius: 4px;
    border-bottom-right-radius: 4px;
}

:deep(.ql-toolbar) {
    border-top-left-radius: 4px;
    border-top-right-radius: 4px;
    background: #fafafa;
}

:deep(.ql-editor) {
    line-height: 1.6;
}

:deep(.ql-editor p) {
    margin-bottom: 1em;
}

:deep(.ql-editor.ql-blank::before) {
    font-style: italic;
    color: #999;
}
</style>