function approveProperty(id) {
  if (!confirm("Apakah Anda yakin ingin menyetujui property ini?")) return;

  fetch("../../../backend/admin/classes/approved_process.php", {
    method: "POST",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
    body: JSON.stringify({ action: "approve", property_id: id })
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
    if (data.success) location.reload();
  })
  .catch(err => console.error(err));
}

function showRejectModal(id, name) {
  document.getElementById("rejectPropertyId").value = id;
  document.getElementById("propertyName").innerText = name;
  document.getElementById("rejectModal").style.display = "block";
}

document.getElementById("rejectForm").addEventListener("submit", e => {
  e.preventDefault();
  const id = document.getElementById("rejectPropertyId").value;
  const reason = document.getElementById("rejectReason").value.trim();

  fetch("../../../backend/admin/classes/approved_process.php", {
    method: "POST",
    headers: { "Content-Type": "application/json", "X-Requested-With": "XMLHttpRequest" },
    body: JSON.stringify({ action: "reject", property_id: id, reason: reason })
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
    if (data.success) location.reload();
  })
  .catch(err => console.error(err));
});
