import { createApp } from 'vue';
import axios from 'axios';
import PosApp from './PosApp.vue';

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf?.content) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.content;
}

const mountPosApp = () => {
    const posAppElement = document.querySelector('#pos-app');

    if (!posAppElement || posAppElement.dataset.posAppMounted === 'true') {
        return;
    }

    posAppElement.dataset.posAppMounted = 'true';
    createApp(PosApp).mount(posAppElement);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountPosApp);
} else {
    mountPosApp();
}

document.addEventListener('livewire:navigated', mountPosApp);
