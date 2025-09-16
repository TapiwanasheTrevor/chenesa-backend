/*
  # Initial Schema Setup for Water Tank Management System

  1. New Tables
    - `profiles`
      - User profiles with roles and contact information
    - `tanks`
      - Water tank information and sensor data
    - `subscriptions`
      - User subscription details and billing
    - `deliveries`
      - Water delivery tracking and management
    - `notifications`
      - System notifications and alerts
    - `delivery_personnel`
      - Delivery staff management
    - `reports`
      - Generated reports and analytics

  2. Security
    - Enable RLS on all tables
    - Add policies for role-based access
    - Secure sensitive data
*/

-- Create enum types
CREATE TYPE user_role AS ENUM ('admin', 'user', 'vendor');
CREATE TYPE subscription_status AS ENUM ('active', 'expired', 'pending');
CREATE TYPE delivery_status AS ENUM ('pending', 'ongoing', 'completed');
CREATE TYPE notification_type AS ENUM ('alert', 'info', 'warning');
CREATE TYPE report_type AS ENUM ('subscription', 'delivery', 'sensor');

-- Profiles table
CREATE TABLE IF NOT EXISTS profiles (
  id uuid PRIMARY KEY REFERENCES auth.users ON DELETE CASCADE,
  role user_role DEFAULT 'user',
  full_name text,
  phone text,
  created_at timestamptz DEFAULT now(),
  updated_at timestamptz DEFAULT now()
);

-- Tanks table
CREATE TABLE IF NOT EXISTS tanks (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  owner_id uuid REFERENCES profiles(id) ON DELETE CASCADE,
  name text NOT NULL,
  location jsonb,
  max_capacity integer NOT NULL,
  current_level integer,
  sensor_id text UNIQUE,
  sensor_status text DEFAULT 'offline',
  battery_voltage numeric(4,2),
  rsrp integer,
  last_reading timestamptz DEFAULT now(),
  created_at timestamptz DEFAULT now()
);

-- Subscriptions table
CREATE TABLE IF NOT EXISTS subscriptions (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id uuid REFERENCES profiles(id) ON DELETE CASCADE,
  plan text NOT NULL,
  status subscription_status DEFAULT 'pending',
  amount numeric(10,2) NOT NULL,
  start_date timestamptz NOT NULL,
  end_date timestamptz NOT NULL,
  created_at timestamptz DEFAULT now()
);

-- Delivery Personnel table
CREATE TABLE IF NOT EXISTS delivery_personnel (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  profile_id uuid REFERENCES profiles(id) ON DELETE CASCADE,
  status text DEFAULT 'available',
  current_location jsonb,
  created_at timestamptz DEFAULT now()
);

-- Deliveries table
CREATE TABLE IF NOT EXISTS deliveries (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  tank_id uuid REFERENCES tanks(id) ON DELETE CASCADE,
  personnel_id uuid REFERENCES delivery_personnel(id),
  status delivery_status DEFAULT 'pending',
  scheduled_date timestamptz NOT NULL,
  completed_date timestamptz,
  amount numeric(10,2),
  rating integer CHECK (rating >= 1 AND rating <= 5),
  feedback text,
  created_at timestamptz DEFAULT now()
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  type notification_type NOT NULL,
  title text NOT NULL,
  message text NOT NULL,
  user_id uuid REFERENCES profiles(id) ON DELETE CASCADE,
  read boolean DEFAULT false,
  created_at timestamptz DEFAULT now()
);

-- Reports table
CREATE TABLE IF NOT EXISTS reports (
  id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
  type report_type NOT NULL,
  title text NOT NULL,
  data jsonb NOT NULL,
  generated_by uuid REFERENCES profiles(id),
  created_at timestamptz DEFAULT now()
);

-- Enable Row Level Security
ALTER TABLE profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE tanks ENABLE ROW LEVEL SECURITY;
ALTER TABLE subscriptions ENABLE ROW LEVEL SECURITY;
ALTER TABLE delivery_personnel ENABLE ROW LEVEL SECURITY;
ALTER TABLE deliveries ENABLE ROW LEVEL SECURITY;
ALTER TABLE notifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE reports ENABLE ROW LEVEL SECURITY;

-- Create policies
CREATE POLICY "Admins can do everything"
  ON profiles
  TO authenticated
  USING (auth.jwt() ->> 'role' = 'admin')
  WITH CHECK (auth.jwt() ->> 'role' = 'admin');

CREATE POLICY "Users can read own profile"
  ON profiles
  FOR SELECT
  TO authenticated
  USING (auth.uid() = id);

CREATE POLICY "Admins can manage tanks"
  ON tanks
  TO authenticated
  USING (EXISTS (
    SELECT 1 FROM profiles
    WHERE profiles.id = auth.uid()
    AND profiles.role = 'admin'
  ));

CREATE POLICY "Users can view own tanks"
  ON tanks
  FOR SELECT
  TO authenticated
  USING (owner_id = auth.uid());

CREATE POLICY "Admins can manage subscriptions"
  ON subscriptions
  TO authenticated
  USING (EXISTS (
    SELECT 1 FROM profiles
    WHERE profiles.id = auth.uid()
    AND profiles.role = 'admin'
  ));

CREATE POLICY "Users can view own subscriptions"
  ON subscriptions
  FOR SELECT
  TO authenticated
  USING (user_id = auth.uid());

-- Create functions
CREATE OR REPLACE FUNCTION check_water_levels()
RETURNS trigger AS $$
BEGIN
  IF NEW.current_level < (NEW.max_capacity * 0.2) THEN
    INSERT INTO notifications (type, title, message, user_id)
    VALUES (
      'warning',
      'Low Water Level Alert',
      format('Tank %s water level is below 20%%', NEW.name),
      NEW.owner_id
    );
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create triggers
CREATE TRIGGER check_water_levels_trigger
  AFTER UPDATE OF current_level ON tanks
  FOR EACH ROW
  EXECUTE FUNCTION check_water_levels();