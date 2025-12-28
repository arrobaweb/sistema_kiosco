-- Si ya importaste init.sql, ejecuta esto para añadir costo al producto
ALTER TABLE products ADD COLUMN cost DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price;
