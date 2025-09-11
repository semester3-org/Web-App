document.addEventListener("DOMContentLoaded", function() {
  const facilities = [
    "Air Conditioning", "Private Bathroom", "Wi-Fi", "Parking",
    "24/7 Security", "Kitchen", "Smart TV", "Laundry", 
    "Balcony", "Gym", "Swimming Pool", "Pet Friendly"
  ];

  const addBtn = document.getElementById("add-facility-btn");
  const container = document.getElementById("facility-container");
  const selected = document.getElementById("selected-facilities");

  addBtn.addEventListener("click", () => {
    // wrapper untuk search + list
    const wrapper = document.createElement("div");
    wrapper.classList.add("mb-3");

    // input search
    const search = document.createElement("input");
    search.type = "text";
    search.classList.add("form-control", "mb-2");
    search.placeholder = "Search facility...";

    // list hasil
    const list = document.createElement("div");
    list.classList.add("list-group");

    wrapper.appendChild(search);
    wrapper.appendChild(list);
    container.insertBefore(wrapper, selected);

    // fungsi render hasil
    const render = (keyword = "") => {
      list.innerHTML = "";
      const filtered = facilities.filter(f =>
        f.toLowerCase().includes(keyword.toLowerCase())
      );
      filtered.forEach(f => {
        const item = document.createElement("button");
        item.type = "button";
        item.classList.add("list-group-item", "list-group-item-action");
        item.textContent = f;
        item.addEventListener("click", () => {
          if (!Array.from(selected.children).some(tag => tag.dataset.value === f)) {
            const tag = document.createElement("span");
            tag.classList.add("badge", "bg-success", "p-2");
            tag.textContent = f + " ×";
            tag.dataset.value = f;

            const hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = "facilities[]";
            hidden.value = f;
            tag.appendChild(hidden);

            // klik badge untuk hapus
            tag.addEventListener("click", () => tag.remove());

            selected.appendChild(tag);
          }
          wrapper.remove(); // hapus search box setelah pilih
        });
        list.appendChild(item);
      });
    };

    // render awal (semua fasilitas)
    render();

    // auto reload saat mengetik
    search.addEventListener("input", () => {
      render(search.value);
    });
  });
});
