import {createClient} from '@supabase/supabase-js';

const supabaseUrl = 'https://cezfzfrgnhiebmzmbitj.supabase.co';
const supabaseAnonKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImNlemZ6ZnJnbmhpZWJtem1iaXRqIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDExODE2NzIsImV4cCI6MjA1Njc1NzY3Mn0.EupzQYqknNf3qJYtWFVew2cPaBOMy610kEuY141QAJ8';

if (!supabaseUrl || !supabaseAnonKey) {
    throw new Error('Missing Supabase environment variables');
}

export const supabase = createClient(supabaseUrl, supabaseAnonKey);
