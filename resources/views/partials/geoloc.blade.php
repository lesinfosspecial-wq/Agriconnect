<script>
    if (navigator.geolocation && !sessionStorage.getItem("agri-geo")) {
        navigator.geolocation.getCurrentPosition((position) => {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            sessionStorage.setItem("agri-geo", "1");
            document.querySelectorAll("[data-geo-lat]").forEach((champ) => { champ.value = latitude; });
            document.querySelectorAll("[data-geo-lng]").forEach((champ) => { champ.value = longitude; });
            const jeton = document.querySelector('meta[name="csrf-token"]');
            if (!jeton) return;
            fetch("{{ route('localisation') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": jeton.content
                },
                body: JSON.stringify({ latitude, longitude })
            });
        });
    }
</script>
