/**
 * Bootstrap Axios with CSRF token support
 */
import axios from 'axios';

// Set default headers for axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Get CSRF token from meta tag and set it in axios defaults
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = (token as HTMLMetaElement).content;
} else {
    console.error(
        'CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token',
    );
}

export default axios;
