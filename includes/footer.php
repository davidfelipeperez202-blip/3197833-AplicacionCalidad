</div>
    
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function confirmDelete(id, type) {
            if(confirm('¿Está seguro de eliminar este registro?')) {
                window.location.href = type + '.php?delete=' + id;
            }
        }
    </script>
</body>
</html>