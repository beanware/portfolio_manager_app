

<footer class="bg-gray-900 text-white py-5">
    <div class="max-w-full mx-auto px-5">
    <div class="mt-2 flex justify-center">
            <a href="#" target="_blank" class="social-icon facebook w-8 h-8 rounded-full bg-white bg-cover transition-all duration-300 ease-in-out transform hover:scale-110 hover:bg-[rgba(255,255,255,0.2)]" style="background-image: url('./svg/fb.svg');"></a>
            <a href="#" target="_blank" class="social-icon twitter w-8 h-8 rounded-full bg-white bg-cover transition-all duration-300 ease-in-out transform hover:scale-110 hover:bg-[rgba(255,255,255,0.2)]" style="background-image: url('./svg/twitter.svg');"></a>
            <a href="#" target="_blank" class="social-icon instagram w-8 h-8 rounded-full bg-white bg-cover transition-all duration-300 ease-in-out transform hover:scale-110 hover:bg-[rgba(255,255,255,0.2)]" style="background-image: url('./svg/instagram.svg');"></a>
            <a href="#" target="_blank" class="social-icon linkedin w-8 h-8 rounded-full bg-white bg-cover transition-all duration-300 ease-in-out transform hover:scale-110 hover:bg-[rgba(255,255,255,0.2)]" style="background-image: url('./svg/linkedin.svg');"></a>
        </div>
            
    <p class="m-0 text-sm text-center">&copy; 2022 Triad Software. All rights reserved.</p>
        
    </div>
</footer>

<style>
    /* Basic Reset */
    body, h1, h2, p, input, textarea, button, a {
        @apply m-0 p-0 box-border;
    }

    html, body {
        @apply h-full;
    }

    body {
        @apply font-sans bg-gray-100 text-gray-800 flex flex-col justify-between;
    }

    .wrapper {
        @apply flex-1;
    }

    header {
        @apply flex justify-between items-center pb-5 border-b-2 border-gray-300;
    }

    header h1 {
        @apply text-2xl text-[#0d1b2a];
    }

    .button, a.button {
        @apply py-2 px-5 rounded cursor-pointer text-white bg-[#0d1b2a] text-center transition duration-300 ease;
    }

    .button:hover, a.button:hover {
        @apply bg-[rgba(255,255,255,0.1)] text-[#0d1b2a] backdrop-blur-sm;
    }

    form {
        @apply flex flex-col items-center;
    }

    form label {
        @apply block mt-2 font-bold;
    }

    form input, form textarea {
        @apply w-full p-2 mt-1 border border-gray-300 rounded text-sm;
    }

    form textarea {
        @apply resize-y;
    }

    form button {
        @apply mt-5 py-2 px-5 rounded cursor-pointer text-white bg-[#0d1b2a] transition duration-300 ease;
    }

    form button:hover {
        @apply bg-[rgba(255,255,255,0.1)] text-[#0d1b2a] backdrop-blur-sm;
    }

    span {
        @apply text-red-500;
    }

    .whatsapp-icon {
        @apply mt-5 text-center;
    }

    .whatsapp-icon img {
        @apply cursor-pointer w-8 h-8;
        color: #25D366; /* This will remain as it is since it's a color */
    }
</style>
