#!/usr/bin/perl

# usage:
# for class in `./amtc -L`; do ./amtc -E $class YOURAMTHOST; done
#  ... or just look at a specific class ...
# amtc -E AMT_EthernetPortSettings host1   ... to find interesting items first
#  ... then, use this toy to 'pretty' print the reply
# amtc -E AMT_EthernetPortSettings host1..hostN | ppsoap IPAddress MACAddress

# fixme: -> amtc ...?

my @args = @ARGV;
@ARGV=();

while (<>) {
  next if !m#^(\S+)\s+(\S+) .*<g:Items>(.*)</g:Items>#;
  my $host = $1;
  my $wsmclass = $2;
  my $body = $3;
  print "$host $wsmclass ";
  foreach (@args) {
    my $prop = $_;
    my $pat = "<h:$prop>(.*)</h:$prop>";
    if ($body =~ m#$pat#) {
      print "$prop=$1 ";
    } else {
      print "$prop=? ";
    }
  }
  print "\n";
}
