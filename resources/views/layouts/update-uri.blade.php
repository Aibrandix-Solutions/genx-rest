<script>
    function updateLivewireScript() {
        const livewireScript = document.querySelector('script[data-update-uri]');

        if (livewireScript) {
            const updateUri = livewireScript.getAttribute('data-update-uri');
            const currentUrl = window.location.href;

            if (currentUrl.includes('/public')) {
                const publicIndex = currentUrl.indexOf('/public');
                const baseUrl = currentUrl.substring(0, publicIndex + 7); // +7 to include '/public'
                livewireScript.setAttribute('data-update-uri', baseUrl + '/livewire/update');
            }
        }
    }

    // Run on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
        updateLivewireScript();
    });

    // Run on load
    window.addEventListener('load', () => {
        updateLivewireScript();
    });


    document.addEventListener('livewire:navigated', () => {
            // Your function to be called on every route change
        updateLivewireScript();
        
        // Scroll to page title (h1 or h2) after navigation
        setTimeout(() => {
            const mainContent = document.getElementById('main-content');
            if (mainContent) {
                // Find the first h1 or h2 in the main content
                const pageTitle = mainContent.querySelector('h1, h2');
                if (pageTitle) {
                    pageTitle.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    // If no title found, scroll to top
                    mainContent.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }
        }, 100);

    });
</script>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.hook('request', ({ fail }) => {
            fail(({ status, preventDefault }) => {
                if (status === 419) {
                    console.log('Your custom page expiration behavior...');
                    window.location.reload();
                }
            })
        })
    })
</script>

