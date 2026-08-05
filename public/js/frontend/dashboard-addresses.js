function locationSelect() {
    const locationOptionsEl = document.getElementById('address-location-options');
    const locationOptions = locationOptionsEl ? JSON.parse(locationOptionsEl.textContent || '[]') : [];
    return {
        open: false,
        search: '',
        options: locationOptions.reduce((groups, item) => {
            if (!groups[item.province]) groups[item.province] = { province: item.province, cities: {} };
            if (!groups[item.province].cities[item.city]) {
                groups[item.province].cities[item.city] = { city: item.city, options: [] };
            }
            groups[item.province].cities[item.city].options.push({ id: item.id, label: item.label });
            return Object.values(groups).map(p => ({
                province: p.province,
                cities: Object.values(p.cities)
            }));
        }, {}),
        filteredOptions: [],
        init() {
            this.filteredOptions = this.options;
        },
        filterOptions() {
            this.filteredOptions = this.options.map(p => ({
                province: p.province,
                cities: p.cities.map(c => ({
                    city: c.city,
                    options: c.options.filter(o => o.label.toLowerCase().includes(this.search.toLowerCase()) || c.city.toLowerCase().includes(this.search.toLowerCase()))
                })).filter(c => c.options.length)
            })).filter(p => p.cities.length);
        },
        select(option) {
            this.search = option.label;
            document.getElementById('address-sub-district-input').value = option.id;
            this.open = false;
        }
    }
}
