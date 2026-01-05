import { createClient } from '@supabase/supabase-js';

const supabaseUrl = 'https://qnxrpsvzutbmkzwbevcz.supabase.co';
const supabaseAnonKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InFueHJwc3Z6dXRibWt6d2JldmN6Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTU0NzA0ODYsImV4cCI6MjA3MTA0NjQ4Nn0.2uELP43q0FKeBVhTx-zPDZCJ12ERyk1ZtrEyCDSTTcY';

export const supabase = createClient(supabaseUrl, supabaseAnonKey);